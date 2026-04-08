#!/bin/bash
# YiiMP Stratum Control Script
# Compatible with run.sh based stratum system

ALGO="$1"
ACTION="$2"
LOG_FILE="/tmp/stratum_ctl_${ALGO}.log"

# --- CONFIG ---
STRATUM_DIR="/home/crypto-data/yiimp/site/stratum"
RUN_SCRIPT="$STRATUM_DIR/run.sh"
SCREEN_BIN="/usr/bin/screen"

# DEBUG LOGGING
# echo "$(date): Received Algo: $ALGO, Action: $ACTION, Port: $3" >> /tmp/stratum_debug.log

# --- VALIDATE INPUT ---
if [[ -z "$ALGO" || -z "$ACTION" ]]; then
    echo "Usage: $0 <algo> <start|stop|restart|status>"
    exit 1
fi

# --- FUNCTION: CHECK IF RUNNING ---
is_running() {
    $SCREEN_BIN -list | grep -q "\.${ALGO}[[:space:]]"
}

case "$ACTION" in

start)

echo "Starting stratum for $ALGO..." > "$LOG_FILE"

if is_running; then
    echo "Stratum $ALGO already running." >> "$LOG_FILE"
    exit 1
fi

if [ ! -f "$RUN_SCRIPT" ]; then
    echo "run.sh not found at $RUN_SCRIPT" >> "$LOG_FILE"
    exit 1
fi

cd "$STRATUM_DIR" || exit 1

$SCREEN_BIN -dmS "$ALGO" bash "$RUN_SCRIPT" "$ALGO" >> "$LOG_FILE" 2>&1

sleep 3

if is_running; then
    echo "Stratum $ALGO started successfully." >> "$LOG_FILE"
    exit 0
else
    echo "Stratum $ALGO failed to start." >> "$LOG_FILE"
    exit 1
fi
;;

stop)

echo "Stopping stratum for $ALGO..." > "$LOG_FILE"

if is_running; then
    $SCREEN_BIN -S "$ALGO" -X quit
    echo "Stratum $ALGO stopped." >> "$LOG_FILE"
else
    echo "Stratum $ALGO was not running." >> "$LOG_FILE"
fi
;;

restart)

echo "Restarting stratum for $ALGO..." > "$LOG_FILE"

if is_running; then
    $SCREEN_BIN -S "$ALGO" -X quit
    sleep 2
fi

cd "$STRATUM_DIR" || exit 1

$SCREEN_BIN -dmS "$ALGO" bash "$RUN_SCRIPT" "$ALGO" >> "$LOG_FILE" 2>&1

sleep 3

if is_running; then
    echo "Stratum $ALGO restarted successfully." >> "$LOG_FILE"
    exit 0
else
    echo "Stratum $ALGO failed to restart." >> "$LOG_FILE"
    exit 1
fi
;;

status)
    if is_running; then
        echo "ONLINE"
    else
        echo "OFFLINE"
    fi
    ;;

unlock)
    PORT="$3"
    if [[ -z "$PORT" ]]; then
        echo "Port required for unlock" > "$LOG_FILE"
        exit 1
    fi
    echo "Opening port $PORT for $ALGO..." > "$LOG_FILE"
    sudo /usr/sbin/ufw allow "$PORT"/tcp >> "$LOG_FILE" 2>&1
    echo "Port $PORT opened." >> "$LOG_FILE"
    ;;

port-check)
    PORT="$3"
    # ss is the modern replacement for netstat
    if ss -tuln | grep -qE ":$PORT($|[[:space:]])"; then
        echo "OPEN"
    else
        echo "CLOSED"
    fi
    ;;

*)


echo "Invalid action: $ACTION"
echo "Usage: $0 <algo> <start|stop|restart|status>"
exit 1

;;

esac