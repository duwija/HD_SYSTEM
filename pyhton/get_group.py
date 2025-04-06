from telethon import TelegramClient

api_id = '27100075'
api_hash = '8231487461400441459a400320c34cfd'
phone_number = '+6281805460534'

client = TelegramClient('session_name', api_id, api_hash)

async def main():
    await client.start(phone_number)
    dialogs = await client.get_dialogs()
    for dialog in dialogs:
        print(f"Nama: {dialog.name}, ID: {dialog.id}")

with client:
    client.loop.run_until_complete(main())
