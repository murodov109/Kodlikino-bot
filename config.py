import os
from dotenv import load_dotenv

load_dotenv()

# Pyrogram config
API_ID = int(os.getenv("API_ID", 0))
API_HASH = os.getenv("API_HASH", "")
BOT_TOKEN = os.getenv("BOT_TOKEN", "")

# Admin va kanal
ADMIN_ID = int(os.getenv("ADMIN_ID", 0))
CHANNEL_ID = int(os.getenv("CHANNEL_ID", 0))

# Database
DATABASE_NAME = os.getenv("DATABASE_NAME", "bot.db")

# Vaqt sozlamalari (Unix timestamp)
SYNC_TIME = True
