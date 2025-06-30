import Modal from 'flarum/common/components/Modal';
import Button from 'flarum/common/components/Button';
import app from 'flarum/forum/app';
import icon from 'flarum/common/helpers/icon';

/**
 * AI聊天模态窗口组件
 */
export default class AiChatModal extends Modal {
  static isDismissible = true;

  oninit(vnode) {
    super.oninit(vnode);
    this.messages = [];
    this.inputValue = '';
    this.isLoading = false;
    this.conversationHistory = [];
    
    // 添加初始消息
    this.addMessage('Hello! I\'m the Forum AI Assistant. How can I help you today?', 'ai');
    
    // 添加自定义样式
    this.addCustomStyles();
  }

  onshow() {
    this.addCustomStyles();
    
    // 聚焦输入框
    setTimeout(() => {
      const input = this.element.querySelector('.FormControl');
      if (input) input.focus();
    }, 100);
  }
  
  onremove() {
    // 不再需要移除事件监听器
  }

  className() {
    return 'AiChatModal Modal--large';
  }

  title() {
    return [
      icon('fas fa-robot'),
      ' Forum AI Support'
    ];
  }

  content() {
    return (
      <div className="Modal-body">
        <div className="AiChatContent">
          <div className="AiChatMessages" id="ai-chat-messages">
            {this.messages.map((message, index) => (
              <div key={index} className={`AiMessage AiMessage--${message.type}`}>
                {message.type === 'ai' ? (
                  <>
                    <div className="AiMessage-avatar">
                      {icon('fas fa-robot')}
                    </div>
                    <div className="AiMessage-content">
                      {this.formatMessage(message.text)}
                      {message.references && message.references.length > 0 && (
                        <div className="AiMessage-references">
                          <div className="AiReferences-title">{app.translator.trans('ai-support.forum.references_title')}</div>
                          <ul>
                            {message.references
                              .filter(ref => ref.source !== 'knowledge_base')
                              .map((ref, i) => (
                                <li key={i}>
                                  <a href={ref.url} target="_blank" rel="noopener noreferrer">
                                    {ref.title || `Reference ${ref.reference_number}`}
                                  </a>
                                </li>
                              ))}
                          </ul>
                        </div>
                      )}
                    </div>
                  </>
                ) : (
                  <div className="AiMessage-container">
                    <div className="AiMessage-content">
                      {this.formatMessage(message.text)}
                    </div>
                    <div className="AiMessage-avatar">
                      {icon('fas fa-user')}
                    </div>
                  </div>
                )}
              </div>
            ))}
            {this.isLoading && (
              <div className="AiMessage AiMessage--ai">
                <div className="AiMessage-avatar">
                  {icon('fas fa-robot')}
                </div>
                <div className="AiMessage-content">
                  <div className="AiTypingIndicator">
                    <span></span>
                    <span></span>
                    <span></span>
                  </div>
                </div>
              </div>
            )}
          </div>
          <div className="AiChatInputArea">
            <textarea
              className="FormControl"
              placeholder={app.translator.trans('ai-support.forum.placeholder')}
              onkeydown={this.handleKeyPress.bind(this)}
              disabled={this.isLoading}
              value={this.inputValue}
              oninput={(e) => this.inputValue = e.target.value}
              rows="3"
            />
            <div className="AiChatButtons">
              {this.inputValue.trim() && (
                <Button
                  className="Button Button--icon AiChatClearButton"
                  icon="fas fa-times"
                  onclick={() => {
                    this.inputValue = '';
                    m.redraw();
                    // 聚焦输入框
                    setTimeout(() => {
                      const input = this.element.querySelector('.FormControl');
                      if (input) input.focus();
                    }, 10);
                  }}
                  title={app.translator.trans('ai-support.forum.clear')}
                />
              )}
              {Button.component({
                className: "Button Button--primary AiChatSendButton",
                icon: "fas fa-paper-plane",
                onclick: this.sendMessage.bind(this),
                disabled: this.isLoading || !this.inputValue.trim(),
                loading: this.isLoading
              }, app.translator.trans('ai-support.forum.send'))}
            </div>
          </div>
          <div className="AiChatFooter">
            <small>
              {app.forum.attribute('aiSupportEnabled') 
                ? icon('fas fa-circle-check', {className: 'AiStatus--enabled'}) 
                : icon('fas fa-circle-xmark', {className: 'AiStatus--disabled'})}
              {' '}
              AI Support Widget
            </small>
            <div className="AiChatDisclaimer">
              <small>
                <em>Please note that AI responses may not always be accurate and are for reference only. For further assistance, please contact the technical support team or create a post on the forum.</em>
              </small>
            </div>
          </div>
        </div>
      </div>
    );
  }

  formatMessage(text) {
    // 简单的Markdown格式支持
    if (!text) return '';
    
    // 处理代码块
    text = text.replace(/```([a-z]*)\n([\s\S]*?)\n```/g, (match, language, code) => {
      return `<div class="AiCodeBlock"><div class="AiCodeBlock-header">${language || 'code'}</div><pre class="AiCodeBlock-content">${this.escapeHtml(code)}</pre></div>`;
    });
    
    // 处理行内代码
    text = text.replace(/`([^`]+)`/g, '<code>$1</code>');
    
    // 处理粗体
    text = text.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
    
    // 处理斜体
    text = text.replace(/\*([^*]+)\*/g, '<em>$1</em>');
    
    // 处理列表
    text = text.replace(/^\s*[\-\*]\s+(.+)$/gm, '<li>$1</li>');
    text = text.replace(/<li>(.+)(?=\n<li>)/g, '<li>$1</li>');
    text = text.replace(/(<li>.+<\/li>)/, '<ul>$1</ul>');
    
    // 处理标题
    text = text.replace(/^### (.+)$/gm, '<h3>$1</h3>');
    text = text.replace(/^## (.+)$/gm, '<h2>$1</h2>');
    text = text.replace(/^# (.+)$/gm, '<h1>$1</h1>');
    
    // 将文本中的URL转换为链接
    const urlRegex = /(https?:\/\/[^\s]+)/g;
    text = text.replace(urlRegex, url => `<a href="${url}" target="_blank" rel="noopener noreferrer">${url}</a>`);
    
    // 将换行符转换为<br>标签
    text = text.replace(/\n/g, '<br>');
    
    // 返回带有HTML的内容
    return m.trust(text);
  }
  
  escapeHtml(text) {
    const map = {
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
  }

  handleKeyPress(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      this.sendMessage();
    }
  }

  sendMessage() {
    try {
      const message = this.inputValue.trim();
      
      if (!message || this.isLoading) {
        return;
      }
      
      // 清空输入框
      this.inputValue = '';
      
      // 添加用户消息
      this.addMessage(message, 'user');
      
      // 保存对话历史
      this.conversationHistory.push({
        role: 'user',
        content: message
      });
      
      // 设置加载状态
      this.isLoading = true;
      m.redraw();
      
      // 发送到API
      app.request({
        method: 'POST',
        url: app.forum.attribute('apiUrl') + '/ai-support/chat',
        body: { 
          message: message,
          history: this.conversationHistory.slice(-4) // 只发送最近的4条消息作为上下文
        }
      }).then(response => {
        try {
          if (response && response.data && response.data.attributes) {
            const aiResponse = response.data.attributes.response;
            const references = response.data.attributes.references || [];
            
            // 检查是否是限制响应（控制器直接返回的限制消息）
            if (response.data.id === '429') {
              // 用户已达到今日使用限制
              this.addMessage(aiResponse, 'ai', references);
              return;
            }
            
            this.addMessage(aiResponse, 'ai', references);
            
            // 保存AI回复到对话历史
            this.conversationHistory.push({
              role: 'assistant',
              content: aiResponse
            });
          } else {
            // 错误处理
            this.addMessage(app.translator.trans('ai-support.forum.error_message'), 'ai');
          }
        } catch (error) {
          // 错误处理
          this.addMessage(app.translator.trans('ai-support.forum.error_message'), 'ai');
        }
      }).catch(error => {
        let errorMessage = app.translator.trans('ai-support.forum.error_message');
        
        if (error.status === 429) {
          errorMessage = app.translator.trans('ai-support.forum.limit_reached');
          // 用户已达到今日使用限制
          this.addMessage('You have reached your daily usage limit. Please try again tomorrow.', 'ai');
          return; // 直接返回，不显示通用错误信息
        } else if (error.status === 403) {
          errorMessage = app.translator.trans('ai-support.forum.login_required');
        } else if (error.status === 0) {
          errorMessage = app.translator.trans('ai-support.forum.connection_failed');
        }
        
        this.addMessage(errorMessage, 'ai');
      }).finally(() => {
        this.isLoading = false;
        m.redraw();
        
        // 聚焦输入框
        setTimeout(() => {
          const input = this.element.querySelector('.FormControl');
          if (input) input.focus();
        }, 100);
      });
    } catch (error) {
      this.isLoading = false;
      this.addMessage(app.translator.trans('ai-support.forum.error_message'), 'ai');
      m.redraw();
    }
  }
  
  addMessage(text, type, references = []) {
    try {
      this.messages.push({ text, type, references });
      m.redraw();
      
      // 滚动到底部
      setTimeout(() => {
        const messagesContainer = this.element.querySelector('#ai-chat-messages');
        if (messagesContainer) {
          messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
      }, 50);
    } catch (error) {
      // 错误处理
    }
  }
  
  onready() {
    try {
      super.onready();
      
      // 添加自定义样式
      this.addCustomStyles();
      
      // 聚焦输入框
      setTimeout(() => {
        const input = this.element.querySelector('.FormControl');
        if (input) input.focus();
      }, 100);
    } catch (error) {
      // 错误处理
    }
  }
  
  addCustomStyles() {
    // 如果样式已存在，不再添加
    if (document.getElementById('ai-chat-custom-styles')) return;
    
    const style = document.createElement('style');
    style.id = 'ai-chat-custom-styles';
    style.textContent = `
      .AiChatModal .Modal-content {
        max-width: 800px;
        min-height: 600px;
        max-height: 85vh;
        border-radius: 8px;
      }
      
      .AiChatContent {
        display: flex;
        flex-direction: column;
        height: 600px;
        max-height: calc(85vh - 100px);
      }
      
      .AiChatMessages {
        flex: 1;
        overflow-y: auto;
        padding: 15px;
        background: var(--body-bg);
        border-radius: 8px;
        margin-bottom: 10px;
        box-shadow: inset 0 1px 3px rgba(0,0,0,0.05);
      }
      
      .AiMessage {
        display: flex;
        margin-bottom: 15px;
        align-items: flex-start;
      }
      
      .AiMessage--user {
        justify-content: flex-end;
      }
      
      .AiMessage-container {
        display: flex;
        align-items: flex-start;
        width: 100%;
        justify-content: flex-end;
      }
      
      .AiMessage-avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
      }
      
      .AiMessage--ai .AiMessage-avatar {
        margin-right: 10px;
        background: var(--primary-color, #3f51b5);
        color: white;
      }
      
      .AiMessage--user .AiMessage-avatar {
        margin-left: 10px;
        background: var(--secondary-color, #4caf50);
        color: white;
      }
      
      .AiMessage-content {
        padding: 12px 16px;
        border-radius: 18px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.08);
        max-width: calc(100% - 60px);
        overflow-wrap: break-word;
      }
      
      .AiMessage--ai .AiMessage-content {
        border-top-left-radius: 4px;
        background: var(--control-bg);
        color: var(--text-color);
      }
      
      .AiMessage--user .AiMessage-content {
        border-top-right-radius: 4px;
        background: #4b6eaf;
        color: white;
        text-align: left;
      }
      
      .AiChatInputArea {
        display: flex;
        margin-top: 10px;
        gap: 10px;
        background: var(--body-bg);
        padding: 12px;
        border-radius: 20px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        transition: background-color 0.2s ease, box-shadow 0.2s ease;
      }
      
      .AiChatInputArea .FormControl {
        flex: 1;
        border-radius: 20px;
        padding: 12px 16px;
        resize: none;
        border: 1px solid var(--control-bg);
        background: var(--control-bg);
        color: var(--text-color);
        min-height: 50px;
        transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease;
      }
      
      .AiChatButtons {
        display: flex;
        flex-direction: column;
        gap: 5px;
        justify-content: space-between;
      }
      
      .AiChatClearButton {
        background: var(--control-bg);
        border-radius: 50%;
        width: 30px;
        height: 30px;
        padding: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--muted-color);
        transition: all 0.2s;
      }
      
      .AiChatClearButton:hover {
        background: var(--control-color);
        color: var(--text-color);
      }
      
      .AiChatSendButton {
        border-radius: 20px;
        height: 40px;
        width: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
      }
      
      .AiTypingIndicator {
        display: flex;
        align-items: center;
      }
      
      .AiTypingIndicator span {
        height: 8px;
        width: 8px;
        background: var(--primary-color, #3f51b5);
        border-radius: 50%;
        display: inline-block;
        margin: 0 2px;
        animation: ai-typing 1.4s infinite ease-in-out both;
      }
      
      .AiTypingIndicator span:nth-child(1) {
        animation-delay: -0.32s;
      }
      
      .AiTypingIndicator span:nth-child(2) {
        animation-delay: -0.16s;
      }
      
      @keyframes ai-typing {
        0%, 80%, 100% { transform: scale(0); }
        40% { transform: scale(1); }
      }
      
      .AiMessage-references {
        margin-top: 8px;
        padding-top: 8px;
        border-top: 1px solid var(--control-bg);
        font-size: 0.85em;
      }
      
      .AiReferences-title {
        font-weight: bold;
        margin-bottom: 4px;
      }
      
      .AiMessage-references ul {
        margin: 0;
        padding-left: 20px;
      }
      
      .AiChatFooter {
        text-align: center;
        margin-top: 10px;
        color: var(--muted-color);
        padding: 8px;
        border-radius: 8px;
      }
      
      .AiStatus--enabled {
        color: var(--alert-success-color, #4caf50);
      }
      
      .AiStatus--disabled {
        color: var(--alert-error-color, #f44336);
      }
      
      /* 代码块样式 */
      .AiCodeBlock {
        margin: 10px 0;
        border-radius: 6px;
        overflow: hidden;
        background: var(--control-bg);
        border: 1px solid var(--control-bg);
      }
      
      .AiCodeBlock-header {
        padding: 6px 12px;
        background: var(--control-color);
        color: var(--text-color);
        font-family: monospace;
        font-size: 12px;
        text-transform: uppercase;
      }
      
      .AiCodeBlock-content {
        padding: 12px;
        margin: 0;
        overflow-x: auto;
        font-family: monospace;
        font-size: 13px;
        line-height: 1.5;
        color: var(--text-color);
      }
      
      /* 内联代码样式 */
      .AiMessage-content code {
        background: var(--control-bg);
        padding: 2px 4px;
        border-radius: 3px;
        font-family: monospace;
        font-size: 90%;
        color: var(--primary-color);
      }
      
      /* 标题样式 */
      .AiMessage-content h1, 
      .AiMessage-content h2, 
      .AiMessage-content h3 {
        margin: 10px 0;
        font-weight: bold;
      }
      
      .AiMessage-content h1 {
        font-size: 1.5em;
      }
      
      .AiMessage-content h2 {
        font-size: 1.3em;
      }
      
      .AiMessage-content h3 {
        font-size: 1.1em;
      }
      
      /* 列表样式 */
      .AiMessage-content ul {
        margin: 10px 0;
        padding-left: 20px;
      }
      
      .AiMessage-content li {
        margin-bottom: 5px;
      }
    `;
    
    document.head.appendChild(style);
  }
}