import requests
import json

def get_data(server, type, id):
    url = "https://api.i-meto.com/meting/api"
    params = {
        "server": server,
        "type": type,
        "id": id
    }
    try:
        response = requests.get(url, params=params, timeout=10)
        if response.status_code == 200:
            data = response.json()
            return data
        else:
            print(f"Failed to fetch data. Status code: {response.status_code}")
            print(f"Response content: {response.text}")
            return None
    except requests.RequestException as e:
        print(f"Request error: {e}")
        return None

def process_data(data):
    if not data:
        return []
    for item in data:
        item['name'] = item.pop('title', '')
        item['artist'] = item.pop('author', '')
        item['audio'] = item.pop('url', '')
        item['cover'] = item.pop('pic', '')
        item['lrc'] = item.pop('lrc', '')
    return data

def save_to_json(data, filename="QPlayer/QPlayer.json"):
    if not data:
        print("No data to save.")
        return
    with open(filename, "w", encoding="utf-8") as f:
        json.dump(data, f, ensure_ascii=False, indent=4)
    print(f"Data saved to {filename}")

def main(params):
    all_data = []
    for idx, param in enumerate(params):
        server = param.get("server", "netease")
        type = param.get("type", "playlist")
        id = param.get("id", "")
        print(f"Processing {idx + 1}/{len(params)}: server={server}, type={type}, id={id}")
        
        data = get_data(server, type, id)
        if data:
            processed_data = process_data(data)
            all_data.extend(processed_data)  # 使用 extend 合并数据
        else:
            print(f"No data fetched for server={server}, type={type}, id={id}")
    
    # 保存所有数据到一个文件
    save_to_json(all_data)

if __name__ == "__main__":
    # 读取参数文件
    try:
        with open("params.json", "r", encoding="utf-8") as f:
            params = json.load(f)
        main(params)
    except FileNotFoundError:
        print("params.json file not found.")
    except json.JSONDecodeError:
        print("Error decoding params.json file.")
