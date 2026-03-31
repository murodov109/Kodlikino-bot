from pyrogram import Client, Filters, InlineKeyboardButton, InlineKeyboardMarkup
import asyncio
import sqlite3

API_ID = 12345
API_HASH = "your_api_hash"
BOT_TOKEN = "your_bot_token"

app = Client("film_bot", bot_token=BOT_TOKEN, api_id=API_ID, api_hash=API_HASH)

db = sqlite3.connect('subscribers.db')
cursor = db.cursor()
cursor.execute('''CREATE TABLE IF NOT EXISTS users (user_id INTEGER PRIMARY KEY, is_premium BOOLEAN DEFAULT 0)''')
db.commit()

async def check_subscription(user_id):
    # Logic to verify if the user follows the channel
    return True

@app.on_message(Filters.command('start'))
async def start(client, message):
    if not await check_subscription(message.from_user.id):
        await message.reply("Please subscribe to the channel.", reply_markup=InlineKeyboardMarkup([[InlineKeyboardButton("Subscribe", url="https://t.me/your_channel").]]))
        return
    await message.reply("Welcome to the Film Bot!")

@app.on_message(Filters.command('premium'))
async def premium(client, message):
    user_id = message.from_user.id
    if check_subscription(user_id)
        cursor.execute('''UPDATE users SET is_premium = 1 WHERE user_id = ?''', (user_id,))
        db.commit()
    await message.reply("You are now a premium user!")

@app.on_message(Filters.command('list_films'))
async def list_films(client, message):
    films = get_all_films()  
    keyboard = InlineKeyboardMarkup([[InlineKeyboardButton(film.title, callback_data=film.id) for film in films]])
    await message.reply("Here are the available films:", reply_markup=keyboard)

@app.on_callback_query()
async def callback_query_handler(client, callback_query):
    film_id = callback_query.data
    await callback_query.answer(f'You selected film ID: {film_id}')

app.run()