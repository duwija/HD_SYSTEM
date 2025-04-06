from telethon import TelegramClient, events

# Ganti dengan api_id dan api_hash Anda
api_id = '27100075'
api_hash = '8231487461400441459a400320c34cfd'

# Ganti dengan nomor telepon Anda
phone_number = '+6281805360534'

# Ganti dengan username atau ID grup tujuan
group_username_or_id = '-1002259278918'

# Pesan yang ingin dikirim
message = 'Halo, ini pesan dari Telethon!'

# Buat client
client = TelegramClient('session_name', api_id, api_hash)

async def main():
    # Masuk ke akun Telegram
    await client.start(phone_number)

    # Cari grup berdasarkan username atau ID
    group = await client.get_entity(group_username_or_id)

    # Kirim pesan ke grup
    await client.send_message(group, message)

    print("Pesan berhasil dikirim!")

# Jalankan client
with client:
    client.loop.run_until_complete(main())
