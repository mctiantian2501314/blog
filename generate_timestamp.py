import json
import time
import os


current_timestamp = int(time.time())

if not os.path.exists("timestamp"):
    os.makedirs("timestamp")

file_path = "timestamp/sy_token.json"
data = {"timestamp": current_timestamp}
with open(file_path, "w") as f:
    json.dump(data, f)

print(f"时间戳已保存到 {file_path}")
