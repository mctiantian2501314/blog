import os
import json
import datetime
import re

DEFAULT_CONFIG = {
    'generator': {
        'md_dir': 'md',
        'output_dir': 'data',
        'page_size': 10,
        'include_content': True,
        'time_format': '%Y-%m-%d %H:%M:%S',
        'auto_parse_tags': True,
        'allowed_categories': []
    }
}

def load_config():
    config_path = 'blog_config.json'
    if not os.path.exists(config_path):
        with open(config_path, 'w', encoding='utf-8') as f:
            json.dump(DEFAULT_CONFIG, f, ensure_ascii=False, indent=4)
    
    with open(config_path, 'r', encoding='utf-8') as f:
        user_config = json.load(f)
    
    merged_config = DEFAULT_CONFIG.copy()
    if 'generator' in user_config:
        merged_config['generator'].update(user_config['generator'])
    return merged_config

def generate_blog_data():
    try:
        config = load_config()['generator']
        md_dir = config['md_dir']
        output_dir = config['output_dir']
        page_size = config['page_size']
        include_content = config['include_content']
        time_format = config['time_format']
        auto_parse_tags = config['auto_parse_tags']

        os.makedirs(output_dir, exist_ok=True)

        posts = []
        categories = set()
        archives = set()
        tags = set()

        for root, dirs, files in os.walk(md_dir):
            for file in files:
                if not file.lower().endswith('.md'):
                    continue
                
                file_path = os.path.join(root, file)
                
                # Process category
                category = os.path.relpath(root, md_dir)
                category = category.replace(os.path.sep, '/')
                if category == '.':
                    category = ''
                
                # File times
                ctime = os.path.getctime(file_path)
                mtime = os.path.getmtime(file_path)
                created = datetime.datetime.fromtimestamp(ctime).strftime(time_format)
                modified = datetime.datetime.fromtimestamp(mtime).strftime(time_format)
                archive = datetime.datetime.fromtimestamp(ctime).strftime('%Y-%m')
                
                # Parse tags
                post_tags = []
                content = ''
                if auto_parse_tags and include_content:
                    with open(file_path, 'r', encoding='utf-8') as f:
                        content = f.read()
                if auto_parse_tags:
                    tag_match = re.search(r'\[标签\](.*?)\[/标签\]', content, re.IGNORECASE | re.DOTALL)
                    if tag_match:
                        tags_str = tag_match.group(1).strip()
                        post_tags = [t.strip() for t in re.split(r'[,|]', tags_str) if t.strip()]
                
                # Collect metadata
                if category:
                    categories.add(category)
                archives.add(archive)
                tags.update(post_tags)
                
                # Build post entry
                rel_path = os.path.relpath(file_path, md_dir).replace(os.path.sep, '/')
                posts.append({
                    'title': os.path.splitext(file)[0],
                    'created': created,
                    'modified': modified,
                    'category': category,
                    'archive': archive,
                    'path': rel_path,
                    'tags': post_tags,
                    '_timestamp': ctime
                })

        # Sort posts
        posts.sort(key=lambda x: x['_timestamp'], reverse=True)
        for post in posts:
            del post['_timestamp']

        # Process aggregates
        categories = sorted(categories)
        archives = sorted(archives, reverse=True)
        tags = sorted(tags)

        # Pagination
        total = len(posts)
        chunks = [posts[i:i+page_size] for i in range(0, total, page_size)]
        page_count = len(chunks)

        # Generate pages
        for idx, chunk in enumerate(chunks, 1):
            output = {
                'meta': {
                    'page': idx,
                    'page_size': page_size,
                    'total': total,
                    'generated': datetime.datetime.now().isoformat()
                },
                'posts': chunk
            }
            with open(os.path.join(output_dir, f'page_{idx}.json'), 'w', encoding='utf-8') as f:
                json.dump(output, f, ensure_ascii=False, indent=4)

        # Generate meta
        meta = {
            'total': total,
            'page_size': page_size,
            'page_count': page_count,
            'last_updated': datetime.datetime.now().isoformat(),
            'data_files': [f'page_{i}.json' for i in range(1, page_count+1)],
            'categories': categories,
            'archives': archives,
            'tags': list(tags)
        }
        with open(os.path.join(output_dir, 'meta.json'), 'w', encoding='utf-8') as f:
            json.dump(meta, f, ensure_ascii=False, indent=4)

        print(f"生成成功！\n文章总数：{total}\n分页数量：{page_count} 页")
        print(f"分类数量：{len(categories)}\n归档数量：{len(archives)}\n标签数量：{len(tags)}")
        print(f"输出目录：{output_dir}\n元数据文件：{os.path.join(output_dir, 'meta.json')}")

    except Exception as e:
        print(f"生成失败：{str(e)}")
        exit(1)

if __name__ == '__main__':
    generate_blog_data()