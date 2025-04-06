from telethon import TelegramClient
import sys

api_id = '27100075'
api_hash = '8231487461400441459a400320c34cfd'
phone_number = '+6281805360534'

# Ambil argumen dari command line
group_id = int(sys.argv[1])  # ID grup
message = sys.argv[2]        # Pesan
client = TelegramClient('../telegram/session_telegram', api_id, api_hash)

#client = TelegramClient('session_name', api_id, api_hash)

async def main():
    await client.start(phone_number)
    try:
        group = await client.get_input_entity(group_id)
        await client.send_message(group, message)
        print(f"Pesan berhasil dikirim ke grup: {group_id}")
    except Exception as e:
        print(f"Error: {e}")

with client:
    client.loop.run_until_complete(main())
