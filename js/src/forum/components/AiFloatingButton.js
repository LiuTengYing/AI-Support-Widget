import Component from 'flarum/common/Component';
import Button from 'flarum/common/components/Button';
import app from 'flarum/forum/app';
import AiChatModal from './AiChatModal';
import icon from 'flarum/common/helpers/icon';

/**
 * AI助手浮动按钮组件
 */
export default class AiFloatingButton extends Component {
  view() {
    try {
      // 检查用户是否有权限使用AI助手
      const canUseAiSupport = app.forum.attribute('canUseAiSupport');
      
      // 如果用户没有权限，不显示按钮
      if (!canUseAiSupport) {
        return null;
      }
      
      return Button.component({
        className: 'Button Button--icon AiSupportFloatingButton',
        onclick: () => {
          try {
            app.modal.show(AiChatModal);
          } catch (error) {
            // 使用翻译字符串
            alert(app.translator.trans('ai-support.forum.error_message'));
          }
        },
        icon: 'fas fa-robot',
        'aria-label': app.translator.trans('ai-support.forum.ai_support')
      });
    } catch (e) {
      return null;
    }
  }
  
  oncreate(vnode) {
    super.oncreate(vnode);
    this.addCustomStyles();
  }
  
  addCustomStyles() {
    // 如果样式已存在，不再添加
    if (document.getElementById('ai-floating-button-styles')) return;
    
    const style = document.createElement('style');
    style.id = 'ai-floating-button-styles';
    style.textContent = `
      .AiSupportFloatingButton {
        position: fixed;
        bottom: 20px;
        right: 20px;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        z-index: 1000;
        color: white;
        background-color: var(--primary-color, #3f51b5);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        transition: transform 0.2s ease, background-color 0.2s ease;
        border: none;
      }
      
      .AiSupportFloatingButton:hover {
        transform: scale(1.05);
        background-color: var(--primary-color-hover, #303f9f);
      }
      
      .AiSupportFloatingButton:active {
        transform: scale(0.95);
      }
      
      .AiSupportFloatingButton .icon {
        font-size: 20px;
      }
    `;
    
    document.head.appendChild(style);
  }
} 