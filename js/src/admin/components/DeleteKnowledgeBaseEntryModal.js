import app from 'flarum/admin/app';
import Modal from 'flarum/common/components/Modal';
import Button from 'flarum/common/components/Button';

export default class DeleteKnowledgeBaseEntryModal extends Modal {
  oninit(vnode) {
    super.oninit(vnode);
    this.entry = this.attrs.entry;
  }
  
  className() {
    return 'DeleteKnowledgeBaseEntryModal Modal--small';
  }
  
  title() {
    return 'Confirm Deletion';
  }
  
  content() {
    return (
      <div className="Modal-body">
        <p>Are you sure you want to delete this knowledge base entry? This action cannot be undone.</p>
        
        <div className="Form-group">
          <Button
            className="Button Button--primary Button--danger"
            onclick={() => {
              this.attrs.onconfirm();
              this.hide();
            }}
          >
            Delete
          </Button>
          {' '}
          <Button
            className="Button"
            onclick={() => this.hide()}
          >
            Cancel
          </Button>
        </div>
      </div>
    );
  }
} 