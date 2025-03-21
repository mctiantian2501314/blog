import os
import json

def merge_json_files(output_path):
    """
    合并 data/ 目录下的所有分页 JSON 文件中的 posts 数据，并将结果保存到指定的输出文件中。
    :param output_path: 输出文件的路径
    """
    data_dir = 'data'
    all_posts = []

    # 遍历 data/ 目录下的所有文件
    for filename in os.listdir(data_dir):
        if filename.endswith('.json') and filename.startswith('page_'):
            file_path = os.path.join(data_dir, filename)
            with open(file_path, 'r', encoding='utf-8') as f:
                try:
                    data = json.load(f)
                    # 检查是否存在 posts 字段
                    if 'posts' in data:
                        all_posts.extend(data['posts'])
                    else:
                        print(f"跳过文件 {filename}，因为它不包含 'posts' 字段")
                except json.JSONDecodeError as e:
                    print(f"解析文件 {filename} 时出错：{e}")

    # 将合并后的 posts 数据保存到输出文件
    with open(output_path, 'w', encoding='utf-8') as f:
        json.dump({'posts': all_posts}, f, ensure_ascii=False, indent=4)

    print(f"所有 posts 数据已合并到 {output_path}")

if __name__ == '__main__':
    output_file = 'data/data.json'
    merge_json_files(output_file)
