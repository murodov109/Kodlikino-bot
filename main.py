import os
import asyncio
from pyrogram import Client, filters
from pyrogram.types import Message, InlineKeyboardMarkup, InlineKeyboardButton, CallbackQuery
from pyrogram.errors import UserNotParticipant, FloodWait
from dotenv import load_dotenv
import sqlite3
from datetime import datetime, timedelta

load_dotenv()

API_ID = int(os.getenv("API_ID"))
API_HASH = os.getenv("API_HASH")
BOT_TOKEN = os.getenv("BOT_TOKEN")
ADMIN_IDS = [int(os.getenv("ADMIN_ID"))]

app = Client("kodlikino_bot", api_id=API_ID, api_hash=API_HASH, bot_token=BOT_TOKEN)

conn = sqlite3.connect("bot.db", check_same_thread=False)
cursor = conn.cursor()

cursor.execute("""CREATE TABLE IF NOT EXISTS users (
    user_id INTEGER PRIMARY KEY,
    username TEXT,
    is_premium INTEGER DEFAULT 0,
    premium_until TEXT
)""")

cursor.execute("""CREATE TABLE IF NOT EXISTS films (
    film_id INTEGER PRIMARY KEY AUTOINCREMENT,
    code TEXT UNIQUE,
    name TEXT,
    video_id TEXT,
    duration INTEGER,
    size REAL
)""")

cursor.execute("""CREATE TABLE IF NOT EXISTS channels (
    channel_id INTEGER PRIMARY KEY AUTOINCREMENT,
    channel_name TEXT UNIQUE
)""")

cursor.execute("""CREATE TABLE IF NOT EXISTS settings (
    setting_key TEXT PRIMARY KEY,
    setting_value TEXT
)""")

conn.commit()

movies = {}
channels = []
admin_list = ADMIN_IDS.copy()

def is_admin(user_id):
    return user_id in admin_list

async def check_subscription(user_id):
    if not channels:
        return True
    for channel in channels:
        try:
            await app.get_chat_member(channel, user_id)
        except UserNotParticipant:
            return False
        except:
            continue
    return True

def get_admin_keyboard():
    return InlineKeyboardMarkup([
        [InlineKeyboardButton("📊 Statistika", callback_data="stats")],
        [InlineKeyboardButton("➕ Kanal qo'shish", callback_data="add_channel")],
        [InlineKeyboardButton("📋 Kanallar", callback_data="show_channels")],
        [InlineKeyboardButton("🎬 Film qo'shish", callback_data="add_film")],
        [InlineKeyboardButton("🗑 Film o'chirish", callback_data="delete_film")],
        [InlineKeyboardButton("👤 Admin qo'shish", callback_data="add_admin")],
    ])

@app.on_message(filters.command("start") & filters.private)
async def start(client, message: Message):
    user_id = message.from_user.id
    
    cursor.execute("INSERT OR IGNORE INTO users (user_id, username) VALUES (?, ?)", 
                   (user_id, message.from_user.username or "Unknown"))
    conn.commit()
    
    if is_admin(user_id):
        await message.reply_text("🎬 **KodliKino Bot Admin Paneli**\n\nAdmin buyruqlarini tanlang:", 
                                reply_markup=get_admin_keyboard())
    else:
        if not await check_subscription(user_id):
            buttons = []
            for ch in channels:
                buttons.append([InlineKeyboardButton(f"📢 {ch}", url=f"https://t.me/{ch}")])
            buttons.append([InlineKeyboardButton("✅ Tekshirish", callback_data="check_sub")])
            
            await message.reply_text("❌ **Botdan foydalanish uchun kanallarga obuna bo'ling:**",
                                    reply_markup=InlineKeyboardMarkup(buttons))
        else:
            await message.reply_text("🎬 **Film kodini kiriting:**")

@app.on_message(filters.private & filters.text & ~filters.command("start"))
async def handle_text(client, message: Message):
    user_id = message.from_user.id
    text = message.text.strip()
    
    if is_admin(user_id):
        if text.startswith("kanal:"):
            channel = text.replace("kanal:", "").strip()
            channels.append(channel)
            cursor.execute("INSERT INTO channels (channel_name) VALUES (?)", (channel,))
            conn.commit()
            await message.reply_text(f"✅ Kanal qo'shildi: {channel}")
            
        elif text.startswith("film:"):
            parts = text.replace("film:", "").split("|")
            if len(parts) >= 2:
                code = parts[0].strip()
                name = parts[1].strip()
                movies[code] = {"name": name}
                cursor.execute("INSERT INTO films (code, name) VALUES (?, ?)", (code, name))
                conn.commit()
                await message.reply_text(f"✅ Film qo'shildi:\n📝 Kod: {code}\n🎬 Nomi: {name}")
            else:
                await message.reply_text("❌ Format: film:kod|Film Nomi")
                
        elif text.startswith("del:"):
            code = text.replace("del:", "").strip()
            if code in movies:
                del movies[code]
                cursor.execute("DELETE FROM films WHERE code = ?", (code,))
                conn.commit()
                await message.reply_text(f"✅ Film o'chirildi: {code}")
            else:
                await message.reply_text("❌ Film topilmadi")
    else:
        if not await check_subscription(user_id):
            await message.reply_text("❌ Avval kanallarga obuna bo'ling")
            return
        
        if text in movies:
            film = movies[text]
            await message.reply_text(f"🎬 **{film['name']}**\n\n🔢 Kod: `{text}`")
        else:
            await message.reply_text("❌ Film topilmadi")

@app.on_callback_query()
async def handle_callback(client, callback_query: CallbackQuery):
    user_id = callback_query.from_user.id
    data = callback_query.data
    
    if not is_admin(user_id):
        if data == "check_sub":
            if await check_subscription(user_id):
                await callback_query.message.delete()
                await client.send_message(user_id, "✅ **Obuna tasdiqlandi!**\n\n🎬 Film kodini kiriting")
            else:
                await callback_query.answer("❌ Barcha kanallarga obuna bo'ling!", show_alert=True)
        return
    
    if data == "stats":
        cursor.execute("SELECT COUNT(*) FROM users")
        user_count = cursor.fetchone()[0]
        cursor.execute("SELECT COUNT(*) FROM films")
        film_count = cursor.fetchone()[0]
        cursor.execute("SELECT COUNT(*) FROM channels")
        channel_count = cursor.fetchone()[0]
        
        msg = f"📊 **Bot Statistikasi**\n\n"
        msg += f"👥 Foydalanuvchilar: {user_count}\n"
        msg += f"🎬 Filmlar: {film_count}\n"
        msg += f"📢 Kanallar: {channel_count}"
        
        await callback_query.message.edit_text(msg, reply_markup=get_admin_keyboard())
    
    elif data == "add_channel":
        await callback_query.message.edit_text("📢 Kanal nomini yuboring (format: kanal:@kanalname)")
    
    elif data == "show_channels":
        msg = "📋 **Majburiy obuna kanallari:**\n\n"
        if channels:
            for i, ch in enumerate(channels, 1):
                msg += f"{i}. {ch}\n"
        else:
            msg += "❌ Kanallar qo'shilmagan"
        await callback_query.message.edit_text(msg, reply_markup=get_admin_keyboard())
    
    elif data == "add_film":
        await callback_query.message.edit_text("🎬 Film qo'shish (format: film:kod|Film Nomi)")
    
    elif data == "delete_film":
        msg = "🎬 **Filmlar ro'yxati:**\n\n"
        if movies:
            for code, film in movies.items():
                msg += f"📝 Kod: {code}\n🎬 Nomi: {film['name']}\n\n"
            msg += "O'chirish uchun del:kod yuboring"
        else:
            msg += "❌ Filmlar qo'shilmagan"
        await callback_query.message.edit_text(msg)
    
    elif data == "add_admin":
        await callback_query.message.edit_text("👤 Admin ID raqamini yuboring")

app.run()
