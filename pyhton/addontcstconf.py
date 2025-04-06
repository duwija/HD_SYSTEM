import sys
import telnetlib
import time
import datetime
import logging
import socket



ip = sys.argv[1]
login = sys.argv[2]
password = sys.argv[3]
port = sys.argv[4]
timeout = sys.argv[5]
pon_int = sys.argv[6]
onu_int = sys.argv[7]
onu_num = sys.argv[8]
sn = sys.argv[9]
onutype = sys.argv[10]
vlan = sys.argv[11]
username_pppoe = sys.argv[12]
password_pppoe = sys.argv[13]
description = sys.argv[14]
vlanname = sys.argv[15]
tconprofile = sys.argv[16]
gemportprofileup = sys.argv[17]
gemportprofiledown = sys.argv[18]
customer_name = sys.argv[19]
# Set up logging
logging.basicConfig(filename='olt_log.log', level=logging.INFO)

def telnet_olt(host, port, username, password, commands, log_path):
    try:
        # Generate log filename based on current date
        today = datetime.datetime.now().strftime("%Y-%m-%d")
        log_filename = f"olt_log_{today}.log"
        log_file_path = f"{log_path}/{log_filename}"

        # Open the log file
        with open(log_file_path, 'a') as log_file:
            # Connect to the OLT via Telnet
            tn = telnetlib.Telnet(host, port, timeout=10)

            # Wait for the initial welcome message
            output = tn.read_until(b"Username:", timeout=10).decode('ascii')
#            log_file.write(f"{datetime.datetime.now()}: Initial output from OLT:\n{output}\n")

            # Send the username and password
            tn.write(username.encode('ascii') + b"\n")
            output = tn.read_until(b"Password:", timeout=10).decode('ascii')
            tn.write(password.encode('ascii') + b"\n")

            # Wait for the final prompt
            output = tn.read_until(b"#", timeout=10).decode('ascii')
#            log_file.write(f"{datetime.datetime.now()}: Output after sending password:\n{output}\n")

            # Execute commands
            for command in commands:
                tn.write(command.encode('ascii') + b"\n")
                output = tn.read_until(b"#", timeout=10).decode('ascii')
#                log_file.write(f"{datetime.datetime.now()}: Output after command '{command}':\n{output}\n")

                if "GPON ONU sn already exists" in output:
                    log_file.write(f"{datetime.datetime.now()}: Error: The entry is existed. Stopping command execution.\n")
                    print("Error: GPON ONU sn already exists!")
                    success = False
                    break
                else:

                # Check if the output contains the error message
                    if "The entry is existed" in output:
                    	log_file.write(f"{datetime.datetime.now()}: Error: The entry is existed. Stopping command execution.\n")
                    	print("Error: The ONU ID Already Used!")
                    	success = False
                    	break
                    else:
                    	success = True



            # Get final output after executing all commands
            output = tn.read_until(b"#", timeout=10).decode('ascii')
            log_file.write(f"{datetime.datetime.now()}: Final command output:\n{output}\n")

            if success:
            # Verify configuration
            # onu_int = 'gpon-onu_1/2/4'
            # onu_num = '124'
            	verify_command = f"show run interface {onu_int}:{onu_num}"
            	tn.write(verify_command.encode('ascii') + b"\n")
            	output = tn.read_until(b"#", timeout=10).decode('ascii')
#            	log_file.write(f"{datetime.datetime.now()}: Output after verify command '{verify_command}':\n{output}\n")

            # Check if the configuration is successful
            # description = 'ini test aja'
            # tconprofile = '100M'
            # gemportprofileup = '100M'
            # gemportprofiledown = '100M'
            # vlan = '200'
            	if f"description {description}" in output and f"tcont 1 profile {tconprofile}" in output and "gemport 1 tcont 1" in output and f"service-port 1 vport 1 user-vlan {vlan} vlan {vlan}" in output:
#                	log_file.write(f"{datetime.datetime.now()}: Configuration is successful.\n")
                	print("success:Configuration successful!")
                	write="write"
                	tn.write(write.encode('ascii') + b"\n")

            	else:
#                	log_file.write(f"{datetime.datetime.now()}: Configuration is Failed.\n")
                	print("error:Configuration is Failed!")
            else:
              	log_file.write(f"{datetime.datetime.now()}: Done.\n")


        # Close the Telnet connection
        tn.close()

    except telnetlib.TelnetException as e:
        # Handle Telnet-specific exceptions
        logging.error(f"Telnet error: {e}")
        print(f"Telnet error: {e}")
        # Handle socket-related exceptions
        logging.error(f"Socket error: {e}")
        print(f"Socket error: {e}")
    except Exception as e:
        # Handle other exceptions
        logging.error(f"Error: {e}")
        print(f"Error: {e}")


# Define the commands
commands = [
"configure terminal",
f"interface {pon_int}",
f"onu {onu_num} type {onutype} sn {sn}",
"exit",
f"interface {onu_int}:{onu_num}",
f"name {customer_name}",
f"description {description}",
f"tcont 1 profile {tconprofile}",
"gemport 1 tcont 1",
f"gemport 1 traffic-limit upstream {gemportprofileup} downstream {gemportprofiledown}",
f"service-port 1 vport 1 user-vlan {vlan} vlan {vlan}",
"exit",
#f"pon-onu-mng {onu_int}:{onu_num}",
#f"service internet gemport 1 vlan {vlan}",
#f"wan-ip 1 mode pppoe username {username_pppoe} password {password_pppoe} vlan-profile {vlanname} host 1",
#"wan-ip 1 ping-response enable traceroute-response enable",
#"security-mgmt 1 state enable mode forward protocol web",
#"exit",
"end"
#"write"
]

# Define the log path
log_path = '/var/www/html/billing.alus.co.id/storage/logs'

# Call the telnet_olt function with the defined variables
telnet_olt(ip, port, login, password, commands, log_path)
