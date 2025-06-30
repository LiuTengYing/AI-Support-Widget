import app from 'flarum/admin/app';
import Modal from 'flarum/common/components/Modal';
import Button from 'flarum/common/components/Button';

export default class EditKnowledgeBaseEntryModal extends Modal {
  oninit(vnode) {
    super.oninit(vnode);
    this.entry = this.attrs.entry;
    
    // 确保所有必要的属性都存在
    this.editedEntry = {
      type: this.getEntryType(),
      question: this.entry.question || this.entry.attributes?.question || '',
      answer: this.entry.answer || this.entry.attributes?.answer || this.entry.content || this.entry.attributes?.content || '',
      keywords: this.entry.keywords || this.entry.attributes?.keywords || '',
      category_id: this.entry.category_id || this.entry.attributes?.category_id || null
    };
    
    this.categories = this.attrs.categories || [];
  }
  
  // 获取条目的实际类型
  getEntryType() {
    // 首先尝试从attributes中获取type
    if (this.entry.attributes && this.entry.attributes.type) {
      return this.entry.attributes.type;
    }
    
    // 如果没有attributes或attributes中没有type，则尝试直接获取
    if (this.entry.type && this.entry.type !== 'kb-entries') {
      return this.entry.type;
    }
    
    // 默认返回qa类型
    return 'qa';
  }
  
  className() {
    return 'EditKnowledgeBaseEntryModal Modal--large';
  }
  
  title() {
    return 'Edit Knowledge Base Entry';
  }
  
  content() {
    return (
      <div className="Modal-body">
        <div className="Form">
          <div className="Form-group">
            <label>Type</label>
            <div className="Select">
              <select
                className="FormControl"
                value={this.editedEntry.type}
                onchange={(e) => {
                  this.editedEntry.type = e.target.value;
                  m.redraw();
                }}
              >
                <option value="qa">Q&A Type</option>
                <option value="content">Content Type</option>
              </select>
            </div>
            <div className="helpText">
              Current type: {this.editedEntry.type}
            </div>
          </div>
          
          {this.editedEntry.type === 'qa' && (
            <div className="Form-group">
              <label>Question</label>
              <div className="FormControl-container">
                <input
                  className="FormControl"
                  type="text"
                  value={this.editedEntry.question}
                  oninput={(e) => {
                    this.editedEntry.question = e.target.value;
                  }}
                />
              </div>
              <div className="helpText">
                Current question: {this.editedEntry.question || '(No question)'}
              </div>
            </div>
          )}
          
          {this.editedEntry.type === 'content' && (
            <div className="Form-group">
              <label>Title (Optional)</label>
              <div className="FormControl-container">
                <input
                  className="FormControl"
                  type="text"
                  value={this.editedEntry.question}
                  placeholder="Optional title for content entry"
                  oninput={(e) => {
                    this.editedEntry.question = e.target.value;
                  }}
                />
              </div>
              <div className="helpText">
                Adding a title helps the AI better match this content. If left empty, a title will be generated from the content.
              </div>
            </div>
          )}
          
          <div className="Form-group">
            <label>{this.editedEntry.type === 'qa' ? 'Answer' : 'Content'}</label>
            <div className="FormControl-container">
              <textarea
                className="FormControl"
                rows="5"
                value={this.editedEntry.answer}
                oninput={(e) => {
                  this.editedEntry.answer = e.target.value;
                }}
              />
            </div>
            <div className="helpText">
              Current content length: {(this.editedEntry.answer || '').length} characters
            </div>
          </div>
          
          <div className="Form-group">
            <label>Keywords (comma separated)</label>
            <div className="FormControl-container">
              <input
                className="FormControl"
                type="text"
                value={this.editedEntry.keywords}
                oninput={(e) => {
                  this.editedEntry.keywords = e.target.value;
                }}
              />
            </div>
            <div className="helpText">
              Adding keywords helps the AI match relevant content more accurately
              <br />
              Current keywords: {this.editedEntry.keywords || '(None)'}
            </div>
          </div>
          
          {this.categories.length > 0 && (
            <div className="Form-group">
              <label>Category</label>
              <div className="Select">
                <select
                  className="FormControl"
                  value={this.editedEntry.category_id}
                  onchange={(e) => {
                    this.editedEntry.category_id = e.target.value ? parseInt(e.target.value) : null;
                  }}
                >
                  <option value="">-- Select Category --</option>
                  {this.categories.map(category => (
                    <option value={category.id} key={category.id}>
                      {category.name}
                    </option>
                  ))}
                </select>
              </div>
            </div>
          )}
          
          <div className="Form-group">
            <Button
              className="Button Button--primary"
              type="submit"
              onclick={() => {
                console.log('Saving edited entry:', JSON.stringify(this.editedEntry));
                this.attrs.onsubmit(this.editedEntry);
                this.hide();
              }}
            >
              Save Changes
            </Button>
          </div>
        </div>
      </div>
    );
  }
} 