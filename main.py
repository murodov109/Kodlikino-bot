import os
import asyncio
import logging
from pyrogram import Client, filters
from pyrogram.types import Message, InlineKeyboardMarkup, InlineKeyboardButton, CallbackQuery
from pyrogram.errors import UserNotParticipant, BadMsgNotification, FloodWait
from dotenv import load_dotenv
import sqlite3
from datetime import datetime, timedelta
from config import API_ID, API_HASH, BOT_TOKEN, ADMIN_ID, CHANNEL_ID

# Logging sozlamasi
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

load_dotenv()

app = Client(
    "kodlikino_bot",
    api_id=API_ID,
    api_hash=API_HASH,
    bot_token=BOT_TOKEN,
    sleep_threshold=10,
    workers=4,
    no_updates=False
)

conn = sqlite3.connect("bot.db", check_same_thread=False)
cursor = conn.cursor()

cursor.execute("""CREATE TABLE IF NOT EXISTS users (
    user_id INTEGER PRIMARY KEY,
    username TEXT,
    join_date TEXT,
    last_action TEXT,
    has_join_request INTEGER DEFAULT 0
)""")

cursor.execute("""CREATE TABLE IF NOT EXISTS films (
    film_id INTEGER PRIMARY KEY AUTOINCREMENT,
    code TEXT UNIQUE,
    name TEXT
)""")

cursor.execute("""CREATE TABLE IF NOT EXISTS spam_protection (
    user_id INTEGER PRIMARY KEY,
    last_search TEXT,
    search_count INTEGER DEFAULT 0
)""")

conn.commit()

movies = {}
SPAM_LIMIT = 5
SPAM_WINDOW = 60

def is_admin(user_id):
    return user_id == ADMIN_ID

async def check_join_request(user_id):
    try:
        await app.get_chat_member(CHANNEL_ID, user_id)
        return True
    except UserNotParticipant:
        cursor.execute("SELECT has_join_request FROM users WHERE user_id = ?", (user_id,))
        result = cursor.fetchone()
        return result and result[0] == 1
    except:
        return False

def update_user_action(user_id, action):
    cursor.execute("UPDATE users SET last_action = ? WHERE user_id = ?", 
                   (datetime.now().strftime("%Y-%m-%d %H:%M:%S"), user_id))
    conn.commit()

def check_spam(user_id):
    cursor.execute("SELECT last_search, search_count FROM spam_protection WHERE user_id = ?", (user_id,))
    result = cursor.fetchone()
    
    if not result:
        cursor.execute("INSERT INTO spam_protection (user_id, last_search, search_count) VALUES (?, ?, ?)",
                      (user_id, datetime.now().strftime("%Y-%m-%d %H:%M:%S"), 1))
        conn.commit()
        return True
    
    last_search, search_count = result
    last_search_time = datetime.strptime(last_search, "%Y-%m-%d %H:%M:%S")
    time_diff = (datetime.now() - last_search_time).total_seconds()
    
    if time_diff > SPAM_WINDOW:
        cursor.execute("UPDATE spam_protection SET last_search = ?, search_count = 1 WHERE user_id = ?",
                      (datetime.now().strftime("%Y-%m-%d %H:%M:%S"), user_id))
        conn.commit()
        return True
    
    if search_count >= SPAM_LIMIT:
        return False
    
    cursor.execute("UPDATE spam_protection SET search_count = search_count + 1 WHERE user_id = ?", (user_id,))
    conn.commit()
    return True

def get_main_keyboard():
    return InlineKeyboardMarkup([
        [InlineKeyboardButton("🎬 Filmlarni qidirish", callback_data="search")],
        [InlineKeyboardButton("📋 Kanal", url=f"https://t.me/c/{str(CHANNEL_ID)[4:]}")],
    ])

def get_request_keyboard():
    return InlineKeyboardMarkup([
        [InlineKeyboardButton("📨 Zayavka yuborish", callback_data="send_request")],
        [InlineKeyboardButton("🔄 Tekshirish", callback_data="check_request")],
    ])

def get_admin_keyboard():
    return InlineKeyboardMarkup([
        [InlineKeyboardButton("📊 Statistika", callback_data="admin_stats")],
        [InlineKeyboardButton("🎬 Film qo'shish", callback_data="admin_add_film")],
        [InlineKeyboardButton("📋 Filmlar", callback_data="admin_show_films")],
    ])

@app.on_message(filters.command("start") & filters.private)
async def start(client, message: Message):
    user_id = message.from_user.id
    username = message.from_user.username or "Unknown"
    
    cursor.execute("INSERT OR IGNORE INTO users (user_id, username, join_date) VALUES (?, ?, ?)",
                   (user_id, username, datetime.now().strftime("%Y-%m-%d %H:%M:%S")))
    conn.commit()
    update_user_action(user_id, "start")
    
    if is_admin(user_id):
        await message.reply_text("🎬 **Admin Panel**\n\nBuyruqlarni tanlang:", reply_markup=get_admin_keyboard())
        return
    
    has_request = await check_join_request(user_id)
    
    if has_request:
        await message.reply_text("🎬 **KodliKino botiga xush kelibsiz!**\n\n📽 Film kodini yoki nomini qidiring",
                                reply_markup=get_main_keyboard())
    else:
        await message.reply_text("❌ **Botdan foydalanish uchun kanalga zayavka yuborishingiz kerak**\n\n"
                                "Qadamlar:\n"
                                "1️⃣ Tugmani bosing\n"
                                "2️⃣ Kanalga qo'shilish so'rovi yuboring\n"
                                "3️⃣ 'Tekshirish' tugmasini bosing",
                                reply_markup=get_request_keyboard())

@app.on_message(filters.command("search") & filters.private)
async def search_cmd(client, message: Message):
    user_id = message.from_user.id
    
    if is_admin(user_id):
        await message.reply_text("🔍 Film nomini yuboring:")
        return
    
    has_request = await check_join_request(user_id)
    if not has_request:
        await message.reply_text("❌ Avval kanalga zayavka yuboringiz", reply_markup=get_request_keyboard())
        return
    
    if not check_spam(user_id):
        await message.reply_text("⏰ Tez-tez qidirma. Biroz kuting!")
        return
    
    await message.reply_text("🔍 Film kodini yuboring:")

@app.on_message(filters.private & filters.text & ~filters.command(["start", "search"]))
async def handle_text(client, message: Message):
    user_id = message.from_user.id
    text = message.text.strip()
    
    if is_admin(user_id):
        await message.reply_text(f"📝 Saqlandi: {text}")
        return
    
    has_request = await check_join_request(user_id)
    if not has_request:
        await message.reply_text("❌ Botdan foydalanish uchun avval kanalga zayavka yuboringiz",
                                reply_markup=get_request_keyboard())
        return
    
    if not check_spam(user_id):
        await message.reply_text("⏰ Tez-tez qidirma. Biroz kuting!")
        return
    
    if text in movies:
        film = movies[text]
        await message.reply_text(f"🎬 **{film['name']}**\n\n🔢 Kod: `{text}`")
    else:
        await message.reply_text("❌ Film topilmadi. Boshqa kod yuboning")

@app.on_callback_query()
async def handle_callback(client, callback_query: CallbackQuery):
    try:
        user_id = callback_query.from_user.id
        data = callback_query.data
        
        if not is_admin(user_id) and data != "send_request" and data != "check_request":
            has_request = await check_join_request(user_id)
            if not has_request:
                try:
                    await callback_query.answer("❌ Avval kanalga zayavka yuboringiz!", show_alert=True)
                except Exception as e:
                    logger.warning(f"Answer error: {e}")
                return
        
        if data == "send_request":
            cursor.execute("UPDATE users SET has_join_request = 1 WHERE user_id = ?", (user_id,))
            conn.commit()
            
            try:
                await callback_query.answer("📨 Zayavka yuborildi!", show_alert=False)
            except Exception as e:
                logger.warning(f"Answer error: {e}")
            
            try:
                await asyncio.sleep(0.5)
                await callback_query.message.edit_text("📨 **Zayavka yuborildi!**\n\nTekshirish tugmasini bosing →",
                                                       reply_markup=get_request_keyboard())
            except Exception as e:
                logger.warning(f"Edit error: {e}")
        
        elif data == "check_request":
            has_request = await check_join_request(user_id)
            
            if has_request:
                try:
                    await callback_query.message.delete()
                except Exception as e:
                    logger.warning(f"Delete error: {e}")
                
                try:
                    await asyncio.sleep(0.5)
                    await client.send_message(user_id, "✅ **Xush kelibsiz!**\n\n"
                                                     "Endi botdan to'liq foydalanishingiz mumkin",
                                                     reply_markup=get_main_keyboard())
                except Exception as e:
                    logger.warning(f"Send message error: {e}")
                
                try:
                    await callback_query.answer("✅ Siz kanalga qo'shildingiz!", show_alert=False)
                except Exception as e:
                    logger.warning(f"Answer error: {e}")
            else:
                try:
                    await callback_query.answer("⏳ Hali kanalga qo'shilmadingiz...", show_alert=False)
                except Exception as e:
                    logger.warning(f"Answer error: {e}")
        
        elif data == "search":
            try:
                await callback_query.message.edit_text("🔍 Film kodini yuboring:")
            except Exception as e:
                logger.warning(f"Edit error: {e}")
        
        elif data == "admin_stats":
            try:
                cursor.execute("SELECT COUNT(*) FROM users")
                user_count = cursor.fetchone()[0]
                cursor.execute("SELECT COUNT(*) FROM films")
                film_count = cursor.fetchone()[0]
                cursor.execute("SELECT COUNT(*) FROM users WHERE has_join_request = 1")
                request_count = cursor.fetchone()[0]
                
                msg = f"📊 **Statistika**\n\n"
                msg += f"👥 Foydalanuvchilar: {user_count}\n"
                msg += f"📨 Zayavka: {request_count}\n"
                msg += f"🎬 Filmlar: {film_count}"
                
                await callback_query.message.edit_text(msg, reply_markup=get_admin_keyboard())
            except Exception as e:
                logger.warning(f"Stats error: {e}")
        
        elif data == "admin_add_film":
            try:
                await callback_query.message.edit_text("🎬 Film kodini yuboring (format: kod|Film Nomi):")
            except Exception as e:
                logger.warning(f"Edit error: {e}")
        
        elif data == "admin_show_films":
            try:
                msg = "🎬 **Filmlar:**\n\n"
                if movies:
                    for code, film in movies.items():
                        msg += f"📝 `{code}` - {film['name']}\n"
                else:
                    msg += "❌ Film yo'q"
                await callback_query.message.edit_text(msg, reply_markup=get_admin_keyboard())
            except Exception as e:
                logger.warning(f"Films error: {e}")
    
    except Exception as e:
        logger.error(f"Callback critical error: {e}")

if __name__ == "__main__":
    try:
        app.run()
    except KeyboardInterrupt:
        print("Bot to'xtatildi")
        conn.close()
    except Exception as e:
        logger.error(f"Bot xatosi: {e}")
        conn.close()
