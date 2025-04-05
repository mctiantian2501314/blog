class SearchFloatPlugin {
    constructor(blogInstance) {
        this.blog = blogInstance;
        this.isOpen = false;
        this.currentKeyword = '';
        this.isButtonVisible = true;
        this.isThemeMenuVisible = true; // 新增状态变量，用于控制主题菜单按钮的显示状态
        this.init();
    }

    init() {
        this.createUI();
        this.createVisibilityToggle();
        this.createThemeMenu();
        this.patchMethods();
        this.addStyles();
        this.setupEventListeners();
        this.initThemeMenu();
    }

    createUI() {
        // 搜索按钮
        this.floatBtn = document.createElement('div');
        this.floatBtn.className = 'search-float-btn';
        this.floatBtn.innerHTML = '🔍';
        this.floatBtn.style.zIndex = '9998';

        // 搜索窗口
        this.popup = document.createElement('div');
        this.popup.className = 'search-float-window';
        this.popup.innerHTML = `
            <div class="search-header">
                <input type="text" class="search-input" 
                       placeholder="输入关键词搜索..." 
                       aria-label="搜索文章">
                <span class="close-btn" role="button">×</span>
            </div>
            <div class="search-results-container"></div>
        `;
        this.popup.style.zIndex = '9999';

        document.body.appendChild(this.floatBtn);
        document.body.appendChild(this.popup);
    }

    createVisibilityToggle() {
        // 显示/隐藏开关
        this.toggleBtn = document.createElement('div');
        this.toggleBtn.className = 'search-visibility-toggle';
        this.toggleBtn.innerHTML = '▶';
        this.toggleBtn.style.cssText = `
            position: fixed;
            right: 30px;
            bottom: 30px;
            width: 40px;
            height: 40px;
            background: #ffffff;
            border-radius: 30%;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: #2c3e50;
            transition: all 0.3s ease;
            z-index: 9997;
        `;
        document.body.appendChild(this.toggleBtn);
    }

    createThemeMenu() {
        // 主题切换菜单
        this.themeMenu = document.createElement('select');
        this.themeMenu.className = 'theme-menu';
        this.themeMenu.innerHTML = `
            <option value="auto">🌓 自动</option>
            <option value="light">☀️ 明亮</option>
            <option value="dark">🌙 暗黑</option>
        `;
        document.body.appendChild(this.themeMenu);
    }

    initThemeMenu() {
        const savedTheme = localStorage.getItem('themePreference') || 'auto';
        this.themeMenu.value = savedTheme;
        this.applyTheme(savedTheme);

        this.themeMenu.addEventListener('change', (e) => {
            const mode = e.target.value;
            localStorage.setItem('themePreference', mode);
            this.applyTheme(mode);
        });

        if (savedTheme === 'auto') {
            this.setupSystemListener();
        }
    }

    applyTheme(mode) {
        switch (mode) {
            case 'auto':
                DarkReader.auto();
                this.setupSystemListener();
                break;
            case 'dark':
                DarkReader.enable();
                break;
            case 'light':
                DarkReader.disable();
                break;
        }
    }

    setupSystemListener() {
        const systemTheme = window.matchMedia('(prefers-color-scheme: dark)');
        const handler = (e) => {
            e.matches ? DarkReader.enable() : DarkReader.disable();
        };
        systemTheme.removeEventListener('change', handler);
        systemTheme.addEventListener('change', handler);
    }

    patchMethods() {
        const originalRenderPostList = this.blog.renderPostList.bind(this.blog);
        this.blog.renderPostList = (posts) => {
            const filtered = this.applySearchFilter(posts);
            originalRenderPostList(filtered);
            this.updateResults(filtered);
        };

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

        // 搜索按钮点击
        this.floatBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            this.toggleSearchWindow();
        });

        // 输入搜索
        this.popup.querySelector('.search-input').addEventListener('input', (e) => {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                this.currentKeyword = e.target.value.trim();
                this.blog.state.currentFilter = { type: 'search', value: this.currentKeyword };
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
            if (!this.popup.contains(e.target) && !this.floatBtn.contains(e.target) && this.isOpen) {
                this.closeSearch();
            }
        });

        // ESC键关闭
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen) {
                this.closeSearch();
            }
        });

        // 结果点击
        this.popup.querySelector('.search-results-container').addEventListener('click', (e) => {
            const resultItem = e.target.closest('.result-item');
            if (resultItem) {
                window.location.href = `?path=${resultItem.dataset.path}`;
                this.closeSearch();
            }
        });

        // 显示/隐藏开关
        this.toggleBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            this.toggleButtonVisibility();
        });
    }

    updateResults(posts) {
        const container = this.popup.querySelector('.search-results-container');
        container.innerHTML = posts.length ? 
            this.createResultsList(posts) : 
            '<div class="empty-state">未找到匹配结果</div>';
    }

    createResultsList(posts) {
        return `
            <div class="result-count">找到 ${posts.length} 个结果</div>
            <div class="result-list">
                ${posts.map(post => `
                    <div class="result-item" data-path="${post.path}">
                        <h4>${post.title}</h4>
                        ${post.category ? `<div class="result-category">${post.category}</div>` : ''}
                        ${post.tags?.length ? `
                        <div class="result-tags">
                            ${post.tags.map(t => `<span class="tag">${t}</span>`).join('')}
                        </div>` : ''}
                    </div>
                `).join('')}
            </div>
        `;
    }

    toggleSearchWindow() {
        this.isOpen = !this.isOpen;
        this.popup.style.display = this.isOpen ? 'block' : 'none';
        this.floatBtn.classList.toggle('active', this.isOpen);

        if (this.isOpen) {
            this.positionPopup();
            this.popup.querySelector('.search-input').focus();
        }
    }

    toggleButtonVisibility() {
        this.isButtonVisible = !this.isButtonVisible;
        this.floatBtn.style.display = this.isButtonVisible ? 'flex' : 'none';
        this.toggleThemeMenuVisibility(); // 调用新方法同步主题菜单按钮的显示状态
    }

    toggleThemeMenuVisibility() {
        this.isThemeMenuVisible = !this.isThemeMenuVisible; // 切换主题菜单按钮的显示状态
        this.themeMenu.style.display = this.isThemeMenuVisible ? 'block' : 'none';
        this.toggleBtn.innerHTML = this.isButtonVisible && this.isThemeMenuVisible ? '▶' : '◀';
    }

    positionPopup() {
        const btnRect = this.floatBtn.getBoundingClientRect();
        const popupWidth = 360;
        const viewportPadding = 20;

        let left = btnRect.right - popupWidth;
        let top = btnRect.bottom + 15;

        if (left < viewportPadding) left = viewportPadding;
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
            /* 搜索按钮样式 */
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

            /* 主题菜单样式 */
            .theme-menu {
                position: fixed;
                right: 30px;
                bottom: 80px;
                padding: 8px 12px;
                border-radius: 6px;
                border: 1px solid #ddd;
                background: var(--bg-color, #ffffff);
                color: var(--text-color, #333);
                z-index: 9996;
                cursor: pointer;
                transition: all 0.3s ease;
            }

            .theme-menu:hover {
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                transform: translateY(-2px);
            }

            .theme-menu option {
                background: var(--bg-color, #ffffff);
                color: var(--text-color, #333);
            }

            /* 暗色模式适配 */
            @media (prefers-color-scheme: dark) {
                .theme-menu {
                    --bg-color: #2c2c2c;
                    --text-color: #f0f0f0;
                    border-color: #404040;
                }
            }

            /* 其他原有样式... */
            .search-header { position: relative; margin-bottom: 12px; }
            .search-input {
                width: 100%;
                padding: 12px 16px;
                border: 2px solid #e0e0e0;
                border-radius: 8px;
                font-size: 16px;
                transition: border-color 0.3s;
            }
            .search-input:focus { outline: none; border-color: #3498db; }
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
            .close-btn:hover { color: #e74c3c; }
            .search-results-container { max-height: 50vh; overflow-y: auto; }
            .result-count { color: #7f8c8d; font-size: 14px; margin-bottom: 12px; }
            .result-item {
                padding: 12px;
                border-radius: 8px;
                margin-bottom: 8px;
                transition: background 0.2s;
                cursor: pointer;
                border: 1px solid #e0e0e0;
            }
            .result-item:hover { background: #f8f9fa; }
            .result-item h4 { margin: 0 0 6px 0; color: #2c3e50; font-size: 16px; }
            .result-category { font-size: 12px; color: #3498db; margin-bottom: 4px; }
            .result-tags { display: flex; gap: 4px; flex-wrap: wrap; }
            .result-tags.tag { background: #ecf0f1; padding: 4px 8px; border-radius: 4px; font-size: 12px; }
            .empty-state { text-align: center; color: #95a5a6; padding: 24px; }

            /* 移动端适配 */
            @media (max-width: 768px) {
                .search-float-btn { right: 16px; top: 16px; width: 44px; height: 44px; }
                .search-float-window { width: calc(100% - 32px); left: 16px!important; }
                .theme-menu { right: 16px; bottom: 70px; }
            }
        `;
        document.head.appendChild(style);
    }
}

// 初始化代码
document.addEventListener('DOMContentLoaded', () => {
    const blog = new StaticBlog();
    new SearchFloatPlugin(blog);
    blog.init();
});
