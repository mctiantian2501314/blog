class SearchFloatPlugin {
    constructor(blogInstance) {
        this.blog = blogInstance;
        this.isOpen = false;
        this.currentKeyword = ''; // 初始化当前关键词
        this.isButtonVisible = true; // 控制搜索悬浮按钮是否可见的状态
        this.init();
    }

    init() {
        this.createUI();
        this.createVisibilityToggle();
        this.patchMethods();
        this.addStyles();
        this.setupEventListeners();
    }

    createUI() {
        // 悬浮按钮
        this.floatBtn = document.createElement('div');
        this.floatBtn.className ='search-float-btn';
        this.floatBtn.innerHTML = '🔍';
        this.floatBtn.style.zIndex = '9998'; // 确保按钮在常规内容之上

        // 搜索悬浮窗
        this.popup = document.createElement('div');
        this.popup.className ='search-float-window';
        this.popup.innerHTML = `
            <div class="search-header">
                <input type="text" class="search-input" 
                       placeholder="输入关键词搜索..." 
                       aria-label="搜索文章">
                <span class="close-btn" role="button">×</span>
            </div>
            <div class="search-results-container"></div>
        `;
        this.popup.style.zIndex = '9999'; // 窗口在按钮之上

        document.body.appendChild(this.floatBtn);
        document.body.appendChild(this.popup);
    }

    createVisibilityToggle() {
        // 创建显示/隐藏开关
        this.toggleBtn = document.createElement('div');
        this.toggleBtn.className ='search-visibility-toggle';
        this.toggleBtn.innerHTML = '▶'; // 初始显示为▶
        this.toggleBtn.style.zIndex = '9997'; // 确保在其他内容之上但在搜索按钮之下
        this.toggleBtn.style.position = 'fixed';
        this.toggleBtn.style.right = '30px';
        this.toggleBtn.style.bottom = '30px';
        this.toggleBtn.style.width = '40px';
        this.toggleBtn.style.height = '40px';
        this.toggleBtn.style.background = '#ffffff';
        this.toggleBtn.style.borderRadius = '30%';
        this.toggleBtn.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
        this.toggleBtn.style.cursor = 'pointer';
        this.toggleBtn.style.display = 'flex';
        this.toggleBtn.style.alignItems = 'center';
        this.toggleBtn.style.justifyContent = 'center';
        this.toggleBtn.style.fontSize = '20px';
        this.toggleBtn.style.color = '#2c3e50';
        this.toggleBtn.style.transition = 'all 0.3s ease';

        document.body.appendChild(this.toggleBtn);
    }

    patchMethods() {
        // 劫持原始渲染方法
        const originalRenderPostList = this.blog.renderPostList.bind(this.blog);

        this.blog.renderPostList = (posts) => {
            const filtered = this.applySearchFilter(posts);
            originalRenderPostList(filtered);
            this.updateResults(filtered);
        };

        // 劫持路由返回逻辑
        const originalShowPostList = this.blog.showPostList.bind(this.blog);
        this.blog.showPostList = () => {
            this.closeSearch();
            originalShowPostList();
        };
    }

    applySearchFilter(posts) {
        if (!this.currentKeyword) return posts;
        const keyword = this.currentKeyword.toLowerCase();

        return posts.filter(post =>
            post.title.toLowerCase().includes(keyword) ||
            (post.category?.toLowerCase().includes(keyword)) ||
            (post.tags?.some(tag => tag.toLowerCase().includes(keyword))) ||
            (post.content?.toLowerCase().includes(keyword))
        );
    }

    setupEventListeners() {
        let timeout;

        // 按钮点击交互
        this.floatBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            this.toggleSearchWindow();
        });

        // 输入防抖处理
        this.popup.querySelector('.search-input').addEventListener('input', (e) => {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                this.currentKeyword = e.target.value.trim();
                this.blog.state.currentFilter = {
                    type:'search',
                    value: this.currentKeyword
                };
                this.blog.renderView();
            }, 300);
        });

        // 关闭按钮
        this.popup.querySelector('.close-btn').addEventListener('click', (e) => {
            e.stopPropagation();
            this.closeSearch();
        });

        // 全局点击关闭
        document.addEventListener('click', (e) => {
            if (!this.popup.contains(e.target) &&
                !this.floatBtn.contains(e.target) &&
                this.isOpen) {
                this.closeSearch();
            }
        });

        // ESC键关闭
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen) {
                this.closeSearch();
            }
        });

        // 点击搜索结果跳转
        this.popup.querySelector('.search-results-container').addEventListener('click', (e) => {
            const resultItem = e.target.closest('.result-item');
            if (resultItem) {
                const path = resultItem.dataset.path;
                // 使用 fetch 发起 GET 请求
                const url = `?path=${path}`;
                window.location.href = url;
                this.closeSearch();
            }
        });

        // 显示/隐藏开关点击事件
        this.toggleBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            this.toggleButtonVisibility();
        });
    }

    updateResults(posts) {
        const container = this.popup.querySelector('.search-results-container');
        container.innerHTML = posts.length?
            this.createResultsList(posts) :
            '<div class="empty-state">未找到匹配结果</div>';
    }

    createResultsList(posts) {
        return `
            <div class="result-count">找到 ${posts.length} 个结果</div>
            <div class="result-list">
                ${posts.map(post => `
                    <div class="result-item" data-path="${post.path}" style="border: 1px solid #e0e0e0;">
                        <h4>${post.title}</h4>
                        ${post.category? `<div class="result-category">${post.category}</div>` : ''}
                        ${post.tags?.length? `
                        <div class="result-tags">
                            ${post.tags.map(t => `<span class="tag">${t}</span>`).join('')}
                        </div>` : ''}
                    </div>
                `).join('')}
            </div>
        `;
    }

    toggleSearchWindow() {
        this.isOpen =!this.isOpen;
        this.popup.style.display = this.isOpen? 'block' : 'none';
        this.floatBtn.classList.toggle('active', this.isOpen);

        if (this.isOpen) {
            this.positionPopup();
            this.popup.querySelector('.search-input').focus();
        }
    }

toggleButtonVisibility() {
    this.isButtonVisible = !this.isButtonVisible;
    if (this.isButtonVisible) {
        this.floatBtn.style.display = 'flex'; // 恢复为CSS定义的flex布局
        this.toggleBtn.innerHTML = '▶';
        this.toggleBtn.style.backgroundColor = '#ffffff';
        this.toggleBtn.style.color = '#2c3e50';
    } else {
        this.floatBtn.style.display = 'none';
        this.toggleBtn.innerHTML = '◀';
        this.toggleBtn.style.backgroundColor = '#e0e0e0';
        this.toggleBtn.style.color = '#95a5a6';
    }
}
    positionPopup() {
        const btnRect = this.floatBtn.getBoundingClientRect();
        const popupWidth = 360;
        const viewportPadding = 20;

        // 计算最佳显示位置
        let left = btnRect.right - popupWidth;
        let top = btnRect.bottom + 15;

        // 边界检测
        if (left < viewportPadding) {
            left = viewportPadding;
        }
        if (window.innerWidth - (left + popupWidth) < viewportPadding) {
            left = window.innerWidth - popupWidth - viewportPadding;
        }
        if (window.innerHeight - (top + 300) < viewportPadding) {
            top = btnRect.top - 300 - 15;
        }

        this.popup.style.left = `${left}px`;
        this.popup.style.top = `${top}px`;
    }

    closeSearch() {
        this.isOpen = false;
        this.popup.style.display = 'none';
        this.floatBtn.classList.remove('active');
        this.currentKeyword = '';
        this.popup.querySelector('.search-input').value = '';
        this.blog.state.currentFilter = { type: 'all', value: null };
        this.blog.renderView();
    }

    addStyles() {
        const style = document.createElement('style');
        style.textContent = `
            /* 悬浮按钮样式 */
           .search-float-btn {
                position: fixed;
                right: 30px;
                top: 30px;
                width: 50px;
                height: 50px;
                background: #ffffff;
                border-radius: 50%;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 24px;
                color: #2c3e50;
                transition: all 0.3s ease;
                z-index: 9998;
            }

           .search-float-btn:hover {
                transform: scale(1.1);
                box-shadow: 0 6px 16px rgba(0,0,0,0.2);
            }

           .search-float-btn.active {
                background: #f8f9fa;
            }

            /* 搜索窗口样式 */
           .search-float-window {
                display: none;
                position: fixed;
                width: 360px;
                max-height: 60vh;
                background: #ffffff;
                border-radius: 12px;
                box-shadow: 0 8px 32px rgba(0,0,0,0.2);
                z-index: 9999;
                padding: 16px;
                animation: slideIn 0.3s ease;
            }

            @keyframes slideIn {
                from { opacity: 0; transform: translateY(-10px); }
                to { opacity: 1; transform: translateY(0); }
            }

           .search-header {
                position: relative;
                margin-bottom: 12px;
            }

           .search-input {
                width: 100%;
                padding: 12px 16px;
                border: 2px solid #e0e0e0;
                border-radius: 8px;
                font-size: 16px;
                transition: border-color 0.3s;
            }

           .search-input:focus {
                outline: none;
                border-color: #3498db;
            }

           .close-btn {
                position: absolute;
                right: 12px;
                top: 50%;
                transform: translateY(-50%);
                font-size: 24px;
                cursor: pointer;
                color: #95a5a6;
                padding: 0 8px;
            }

           .close-btn:hover {
                color: #e74c3c;
            }

            /* 搜索结果样式 */
           .search-results-container {
                max-height: 50vh;
                overflow-y: auto;
                padding-right: 8px;
            }

           .result-count {
                color: #7f8c8d;
                font-size: 14px;
                margin-bottom: 12px;
            }

           .result-item {
                padding: 12px;
                border-radius: 8px;
                margin-bottom: 8px;
                transition: background 0.2s;
                cursor: pointer;
                border: 1px solid #e0e0e0; /* 这里再添加一遍样式确保生效 */
            }

           .result-item:hover {
                background: #f8f9fa;
            }

           .result-item h4 {
                margin: 0 0 6px 0;
                color: #2c3e50;
                font-size: 16px;
            }

           .result-category {
                font-size: 12px;
                color: #3498db;
                margin-bottom: 4px;
            }

           .result-tags {
                display: flex;
                gap: 4px;
                flex-wrap: wrap;
            }

           .result-tags.tag {
                background: #ecf0f1;
                padding: 4px 8px;
                border-radius: 4px;
                font-size: 12px;
            }

           .empty-state {
                text-align: center;
                color: #95a5a6;
                padding: 24px;
            }

            /* 移动端适配 */
            @media (max-width: 768px) {
               .search-float-btn {
                    right: 16px;
                    top: 16px;
                    width: 44px;
                    height: 44px;
                    font-size: 20px;
                }

               .search-float-window {
                    width: calc(100% - 32px);
                    left: 16px!important;
                    right: 16px!important;
                    max-width: none;
                }
            }

            /* 显示/隐藏开关样式 */
           .search-visibility-toggle {
                position: fixed;
                right: 20px;
                bottom: 20px;
            }
        `;
        document.head.appendChild(style);
    }
}

// 初始化，这里假设 StaticBlog 是已经定义好的类
document.addEventListener('DOMContentLoaded', () => {
    const blog = new StaticBlog();
    new SearchFloatPlugin(blog);
    blog.init();
});
