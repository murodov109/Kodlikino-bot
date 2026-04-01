import os
import sys
import logging
from pyrogram import Client, filters
from pyrogram.types import Message, InlineKeyboardMarkup, InlineKeyboardButton, CallbackQuery
from pyrogram.errors import UserNotParticipant
from dotenv import load_dotenv
import sqlite3
from datetime import datetime

load_dotenv()

# Logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s'
)
logger = logging.getLogger(__name__)

# Config
API_ID = int(os.getenv("API_ID", 0))
API_HASH = os.getenv("API_HASH", "")
BOT_TOKEN = os.getenv("BOT_TOKEN", "")
ADMIN_ID = int(os.getenv("ADMIN_ID", 0))
CHANNEL_ID = int(os.getenv("CHANNEL_ID", 0))

# Validation
if not API_ID or API_ID == 0:
    logger.error("❌ API_ID o'rnatilmagan!")
    sys.exit(1)
if not API_HASH:
    logger.error("❌ API_HASH o'rnatilmagan!")
    sys.exit(1)
if not BOT_TOKEN:
    logger.error("❌ BOT_TOKEN o'rnatilmagan!")
    sys.exit(1)

logger.info(f"✅ API_ID: {API_ID}")
logger.info(f"✅ API_HASH: {API_HASH[:10]}...")
logger.info(f"✅ BOT_TOKEN: {BOT_TOKEN[:20]}...")
logger.info(f"✅ ADMIN_ID: {ADMIN_ID}")
logger.info(f"✅ CHANNEL_ID: {CHANNEL_ID}")

# Pyrogram app
try:
    app = Client(
        "kodlikino_bot",
        api_id=API_ID,
        api_hash=API_HASH,
        bot_token=BOT_TOKEN,
        workdir="."
    )
    logger.info("✅ Pyrogram Client yaratildi")
except Exception as e:
    logger.error(f"❌ Pyrogram Client xatosi: {e}")
    sys.exit(1)

# Database
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
logger.info("✅ Database yaratildi")

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
    except Exception as e:
        logger.error(f"check_join_request xatosi: {e}")
        return False

def update_user_action(user_id, action):
    try:
        cursor.execute("UPDATE users SET last_action = ? WHERE user_id = ?", 
                       (datetime.now().strftime("%Y-%m-%d %H:%M:%S"), user_id))
        conn.commit()
    except Exception as e:
        logger.error(f"update_user_action xatosi: {e}")

def check_spam(user_id):
    try:
        cursor.execute("SELECT last_search, search_count FROM spam_protection WHERE user_id = ?", (user_id,))
        result = cursor.fetchone()
        
        if not result:
            cursor.execute("INSERT INTO spam_protection (user_id, last_search, search_count) VALUES (?, ?, ?)",
                          (user_id, datetime.now().strftime("%Y-%m-%d %H:%M:%S"), 1))
            conn.commit()
            return True
        
        last_search, search_count = result
        from datetime import datetime as dt
        last_search_time = dt.strptime(last_search, "%Y-%m-%d %H:%M:%S")
        time_diff = (dt.now() - last_search_time).total_seconds()
        
        if time_diff > SPAM_WINDOW:
            cursor.execute("UPDATE spam_protection SET last_search = ?, search_count = 1 WHERE user_id = ?",
                          (dt.now().strftime("%Y-%m-%d %H:%M:%S"), user_id))
            conn.commit()
            return True
        
        if search_count >= SPAM_LIMIT:
            return False
        
        cursor.execute("UPDATE spam_protection SET search_count = search_count + 1 WHERE user_id = ?", (user_id,))
        conn.commit()
        return True
    except Exception as e:
        logger.error(f"check_spam xatosi: {e}")
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
    try:
        user_id = message.from_user.id
        username = message.from_user.username or "Unknown"
        
        logger.info(f"✅ Start: {user_id} - {username}")
        
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
    except Exception as e:
        logger.error(f"❌ Start xatosi: {e}")

@app.on_message(filters.command("search") & filters.private)
async def search_cmd(client, message: Message):
    try:
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
    except Exception as e:
        logger.error(f"❌ Search xatosi: {e}")

@app.on_message(filters.private & filters.text & ~filters.command(["start", "search"]))
async def handle_text(client, message: Message):
    try:
        user_id = message.from_user.id
        text = message.text.strip()
        
        logger.info(f"✅ Text: {user_id} - {text}")
        
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
    except Exception as e:
        logger.error(f"❌ Handle text xatosi: {e}")

@app.on_callback_query()
async def handle_callback(client, callback_query: CallbackQuery):
    try:
        user_id = callback_query.from_user.id
        data = callback_query.data
        
        logger.info(f"✅ Callback: {user_id} - {data}")
        
        if not is_admin(user_id) and data != "send_request" and data != "check_request":
            has_request = await check_join_request(user_id)
            if not has_request:
                await callback_query.answer("❌ Avval kanalga zayavka yuboringiz!", show_alert=True)
                return
        
        if data == "send_request":
            cursor.execute("UPDATE users SET has_join_request = 1 WHERE user_id = ?", (user_id,))
            conn.commit()
            
            await callback_query.answer("📨 Zayavka yuborildi!")
            await callback_query.message.edit_text("📨 **Zayavka yuborildi!**\n\nTekshirish tugmasini bosing →",
                                                   reply_markup=get_request_keyboard())
        
        elif data == "check_request":
            has_request = await check_join_request(user_id)
            
            if has_request:
                await callback_query.message.delete()
                await client.send_message(user_id, "✅ **Xush kelibsiz!**\n\n"
                                                 "Endi botdan to'liq foydalanishingiz mumkin",
                                                 reply_markup=get_main_keyboard())
                await callback_query.answer("✅ Siz kanalga qo'shildingiz!")
            else:
                await callback_query.answer("⏳ Hali kanalga qo'shilmadingiz...", show_alert=True)
        
        elif data == "search":
            await callback_query.message.edit_text("🔍 Film kodini yuboring:")
        
        elif data == "admin_stats":
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
        
        elif data == "admin_add_film":
            await callback_query.message.edit_text("🎬 Film kodini yuboring (format: kod|Film Nomi):")
        
        elif data == "admin_show_films":
            msg = "🎬 **Filmlar:**\n\n"
            if movies:
                for code, film in movies.items():
                    msg += f"📝 `{code}` - {film['name']}\n"
            else:
                msg += "❌ Film yo'q"
            await callback_query.message.edit_text(msg, reply_markup=get_admin_keyboard())
    
    except Exception as e:
        logger.error(f"❌ Callback xatosi: {e}")

def main():
    logger.info("🚀 Bot ishga tushmoqda...")
    try:
        app.run()
    except KeyboardInterrupt:
        logger.info("🛑 Bot to'xtatildi")
        conn.close()
    except Exception as e:
        logger.error(f"❌ Bot xatosi: {e}")
        conn.close()
        sys.exit(1)

if __name__ == "__main__":
    main()
