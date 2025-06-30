import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import AiChatModal from './components/AiChatModal';
import AiFloatingButton from './components/AiFloatingButton';

// 添加内联样式表
function addStylesheet() {
  const style = document.createElement('style');
  style.textContent = `
    .ai-support-floating-button {
          position: fixed;
          bottom: 20px;
          right: 20px;
      width: 50px;
      height: 50px;
      background-color: #3f51b5;
          border-radius: 50%;
          display: flex;
          align-items: center;
          justify-content: center;
          cursor: pointer;
          z-index: 1000;
          color: white;
      font-size: 20px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
      transition: transform 0.2s ease;
    }
    
    .ai-support-floating-button:hover {
      transform: scale(1.05);
    }
    
    .ai-support-floating-button:active {
      transform: scale(0.95);
    }
  `;
  document.head.appendChild(style);
}

// 添加一个全局函数，用于手动添加AI按钮
window.addAiButton = function() {
  try {
    // 确保样式表已添加
    addStylesheet();
    
    const appEl = document.querySelector('.App');
    
    if (appEl) {
      let container = document.getElementById('ai-support-container');
      
      if (!container) {
        container = document.createElement('div');
        container.id = 'ai-support-container';
        appEl.appendChild(container);
        
        m.mount(container, AiFloatingButton);
      }
    }
  } catch (error) {
    // 错误处理
  }
};

// 在页面加载完成后添加按钮
document.addEventListener('DOMContentLoaded', function() {
  setTimeout(function() {
    window.addAiButton();
  }, 1000);
});

app.initializers.add('leot-ai-support-widget', () => {
  try {
    // 在页面加载完成后添加AI浮动按钮
    extend(app, 'mount', () => {
      try {
        // 确保只添加一次按钮
        if (document.getElementById('ai-support-container')) {
          return;
        }
        
        // 创建容器并添加按钮
        const container = document.createElement('div');
        container.id = 'ai-support-container';
        
        // 添加到DOM
        document.querySelector('.App').appendChild(container);
        
        // 挂载组件
        m.mount(container, AiFloatingButton);
      } catch (error) {
        // 错误处理
      }
    });
  } catch (error) {
    // 错误处理
  }
});