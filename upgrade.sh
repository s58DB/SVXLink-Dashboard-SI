#!/bin/bash
# This script is used to upgrade the system for the Permissions required for file handling.

# Define the sudoers file, the source file, the script file, and the config file
SUDOERS_FILE="/etc/sudoers.d/svxlink"
SOURCE_FILE="www-data.sudoers"
SCRIPT_FILE=$(basename "$0")
CONFIG_FILE="include/config.inc.php"
AUTH_FILE="/etc/svxlink/dashboard.auth.ini"
CURRENT_USER=${SUDO_USER:-$(whoami)}
USER_HOME=$(getent passwd "$CURRENT_USER" | cut -d: -f6)
if [ -z "$USER_HOME" ]; then
    USER_HOME="/home/$CURRENT_USER"
fi
LOCAL_SCRIPT_DIR="$USER_HOME/scripts"
SCRIPT_DIR="/var/www/html/scripts"
SERVICE_FILE="/etc/systemd/system/svxlink-node.service"
DTMF_SCRIPT="$LOCAL_SCRIPT_DIR/dtmf_setup.sh"
CLEANUP_SCRIPT="$LOCAL_SCRIPT_DIR/cleanup.sh"
CRON_JOB="01 00 * * * $CLEANUP_SCRIPT"
ECHOLINK_DEST="/usr/share/svxlink/events.d/local/EchoLink.tcl"

is_fully_installed() {
    [ -f "$AUTH_FILE" ] || return 1
    [ -f "$SOURCE_FILE" ] || return 1
    [ -f "$SUDOERS_FILE" ] || return 1
    cmp -s "$SOURCE_FILE" "$SUDOERS_FILE" || return 1
    command -v node >/dev/null 2>&1 || return 1
    command -v npm >/dev/null 2>&1 || return 1
    [ -d "$SCRIPT_DIR/node_modules/ws" ] || return 1
    [ -f "$SERVICE_FILE" ] || return 1
    systemctl is-enabled --quiet svxlink-node.service || return 1
    systemctl is-active --quiet svxlink-node.service || return 1
    [ -f "$DTMF_SCRIPT" ] || return 1
    [ -x "$DTMF_SCRIPT" ] || return 1
    [ -f "$CLEANUP_SCRIPT" ] || return 1
    [ -x "$CLEANUP_SCRIPT" ] || return 1
    [ -f "$ECHOLINK_DEST" ] || return 1
    cmp -s /var/www/html/EchoLink.tcl "$ECHOLINK_DEST" || return 1
    crontab -l 2>/dev/null | grep -Fq "$CRON_JOB" || return 1
    return 0
}

# Function to display an info message using whiptail
show_info() {
  whiptail --title "Information" --msgbox "$1" 8 78
}

if is_fully_installed; then
    show_info "Dashboard upgrade is already installed and up to date. Nothing to do."
    exit 0
fi



# Only proceed if the auth file doesn't exist
if [ ! -f "$AUTH_FILE" ]; then
    # Prompt the user for their dashboard username
    DASHBOARD_USER=$(whiptail --title "Dashboard Username" --inputbox "Please enter your dashboard username:" 8 78 svxlink 3>&1 1>&2 2>&3)

    if [ $? -ne 0 ]; then
        echo "User cancelled the username input."
        exit 1
    fi

    # Prompt the user for their dashboard password
    DASHBOARD_PASSWORD=$(whiptail --title "Dashboard Password" --passwordbox "Please enter your dashboard password:" 8 78 3>&1 1>&2 2>&3)

    if [ $? -ne 0 ]; then
        echo "User cancelled the password input."
        exit 1
    fi

    # Create the auth file with the entered credentials
    sudo bash -c "cat > '$AUTH_FILE' <<EOF
[dashboard]
auth_user = '$DASHBOARD_USER'
auth_pass = '$DASHBOARD_PASSWORD'
EOF"

    # Set ownership and permissions
    sudo chown svxlink:svxlink "$AUTH_FILE"
    sudo chmod 640 "$AUTH_FILE"

    # Optional feedback
    whiptail --title "Success" --msgbox "Dashboard authentication file has been created." 8 78
else 
    show_info "Dashboard authentication file already exists. Skipping creation."
fi


# Check if the source file exists
if [ ! -f "$SOURCE_FILE" ]; then
  whiptail --title "Error" --msgbox "Source file $SOURCE_FILE does not exist. Exiting." 8 78
  exit 1
fi

# Check if the sudoers file exists
if [ -f "$SUDOERS_FILE" ]; then
  : > "$SUDOERS_FILE"
else
  touch "$SUDOERS_FILE"
fi

# Ensure the sudoers file has the correct permissions


# Read the content from the source file into the sudoers file
cat "$SOURCE_FILE" > "$SUDOERS_FILE"

# Inform the user that the operation was successful
show_info "Content from $SOURCE_FILE has been written to $SUDOERS_FILE successfully."

sudo chmod +x /var/www/html/scripts/install_tg_schedule_cron.sh 2>/dev/null || true
sudo chmod +x /var/www/html/scripts/read_svxlink_log.sh 2>/dev/null || true

# Validate the syntax of the sudoers file
visudo -cf "$SUDOERS_FILE"
if [ $? -eq 0 ]; then
  show_info "The $SUDOERS_FILE syntax is valid."
else
  whiptail --title "Error" --msgbox "The $SUDOERS_FILE contains syntax errors. Please check the file." 8 78
  exit 1
fi

# Change ownership of all files in /var/www/html except the script itself
find /var/www/html ! -name "$SCRIPT_FILE" -exec sudo chown svxlink:svxlink {} +
# Change ownership of all files in /etc/svxlink and sub directories.
find /etc/svxlink -type f -exec sudo chown svxlink:svxlink {} +
# Set DTMF active.
sudo mkdir -p /var/run/svxlink
sudo chown svxlink:svxlink /var/run/svxlink
sudo chmod 775 /var/run/svxlink

#find the terminal active.
# Inform the user that the ownership change was successful
show_info "Ownership of files in /var/www/html has been changed to svxlink:svxlink, except for the script itself."
# ==============================
# Node.js, npm, and svxlink-node.service setup
# ==============================

# Check if Node.js is installed
if ! command -v node >/dev/null 2>&1; then
    show_info "Node.js not found. Installing Node.js and npm..."
    sudo apt update
    sudo apt install -y nodejs npm
else
    show_info "Node.js is already installed: $(node -v)"
fi

if ! command -v npm >/dev/null 2>&1; then
    show_info "npm not found. Installing npm..."
    sudo apt install -y npm
else
    show_info "npm is already installed: $(npm -v)"
fi


# Ensure npm cache/home paths exist for the current and service users.
sudo mkdir -p "$USER_HOME/.npm" "$USER_HOME/.npm-global"
sudo chown -R "$CURRENT_USER:$CURRENT_USER" "$USER_HOME/.npm" "$USER_HOME/.npm-global"
sudo chmod -R 755 "$USER_HOME/.npm-global"

SVXLINK_HOME=$(getent passwd svxlink | cut -d: -f6)
if [ -z "$SVXLINK_HOME" ]; then
    SVXLINK_HOME="/home/svxlink"
fi
sudo mkdir -p "$SVXLINK_HOME" "$SVXLINK_HOME/.npm"
sudo chown -R svxlink:svxlink "$SVXLINK_HOME"
sudo chmod 755 "$SVXLINK_HOME"


# Ensure ws module is installed for svxlink user in scripts folder
if [ ! -d "$SCRIPT_DIR/node_modules/ws" ]; then
    show_info "Installing ws Node module for svxlink user..."
    sudo -u svxlink env HOME="$SVXLINK_HOME" npm --prefix "$SCRIPT_DIR" install ws
else
    show_info "Node module ws is already installed."
fi

# Create the systemd service only if it doesn't exist
if [ ! -f "$SERVICE_FILE" ]; then
    show_info "Creating svxlink-node.service..."
    sudo tee "$SERVICE_FILE" > /dev/null <<EOL
[Unit]
Description=SVXLink Node.js Server
After=network.target

[Service]
# Send logs directly to journald instead of syslog or files
StandardOutput=journal
StandardError=journal

# Ensure service restarts even after journal restarts or SIGHUPs
Restart=always
RestartSec=5

# Allow clean reloads (optional, useful if you add reload scripts later)
ExecReload=/bin/kill -HUP \$MAINPID

# Give the process a few seconds to shut down gracefully
TimeoutStopSec=10
Type=simple
User=svxlink
Group=svxlink
ExecStart=/usr/bin/node /var/www/html/scripts/server.js
Environment=NODE_ENV=production

[Install]
WantedBy=multi-user.target
EOL

    show_info "Reloading systemd, enabling, and starting svxlink-node.service..."
    sudo systemctl daemon-reload
    sudo systemctl enable --now svxlink-node.service
    sudo systemctl start svxlink-node.service
else
    show_info "svxlink-node.service already exists."
    # Optional: restart it to ensure it's running
    sudo systemctl restart svxlink-node.service
fi

# Verify service status
sudo systemctl is-active --quiet svxlink-node.service && show_info "svxlink-node.service is running." || show_info "svxlink-node.service is not running!"

# ==============================
# Create the local helper scripts directory if missing.
# ==============================

if [ ! -f "$DTMF_SCRIPT" ]; then
    show_info "Creating $DTMF_SCRIPT..."
    sudo mkdir -p "$LOCAL_SCRIPT_DIR"
    echo "#!/bin/sh
sudo mkdir -p /var/run/svxlink
sudo chown svxlink:svxlink /var/run/svxlink
sudo chmod 775 /var/run/svxlink" | sudo tee "$DTMF_SCRIPT" > /dev/null

    sudo chmod +x "$DTMF_SCRIPT"
    show_info "$DTMF_SCRIPT created and made executable."
else
    show_info "$DTMF_SCRIPT already exists."
fi

# Run the script immediately
show_info "Running $DTMF_SCRIPT..."
sudo "$DTMF_SCRIPT"
# Add Modification to /usr/share/svxlink/events.d/local/EchoLink.tcl
sudo mkdir -p /usr/share/svxlink/events.d/local
if ! cmp -s /var/www/html/EchoLink.tcl "$ECHOLINK_DEST"; then
    sudo cp -f /var/www/html/EchoLink.tcl "$ECHOLINK_DEST"
fi
# New section to create cleanup.sh

# Check if the script directory exists, if not, create it
if [ ! -d "$LOCAL_SCRIPT_DIR" ]; then
    mkdir -p "$LOCAL_SCRIPT_DIR"
    show_info "Created directory $LOCAL_SCRIPT_DIR"
fi

# Check if the cleanup.sh script exists
if [ -f "$CLEANUP_SCRIPT" ]; then
    show_info "Script $CLEANUP_SCRIPT already exists."
else
    # Create the cleanup.sh script with the specified content
    echo "#!/bin/bash

# Directory to be cleaned
DIR=\"/var/www/html/backups\"

# Check if directory exists
if [ -d \"\$DIR\" ]; then
    # Find and delete files older than 7 days
    find \"\$DIR\" -type f -mtime +7 -exec rm -f {} \;
else
    echo \"Directory \$DIR does not exist.\"
fi" > "$CLEANUP_SCRIPT"

    # Make the cleanup.sh script executable
    sudo chmod +x "$CLEANUP_SCRIPT"
    show_info "Created and made $CLEANUP_SCRIPT executable."
fi

# Check and add the cleanup.sh script to the sudo crontab if not already present
( sudo crontab -l 2>/dev/null | grep -Fq "$CRON_JOB" ) || ( sudo crontab -l 2>/dev/null; echo "$CRON_JOB" ) | sudo crontab -

# Inform the user that the crontab entry has been added if it was not present
show_info "Ensured that the crontab entry for $CLEANUP_SCRIPT exists."
