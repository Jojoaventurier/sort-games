import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ["panel", "panelTitle", "panelContent", "gameData"];

    view(event) {
        // Find the template inside the clicked card
        const card = event.currentTarget;
        const template = card.querySelector('template');
        const listName = card.dataset.collectionViewerNameValue;

        // Update the right panel
        this.panelTitleTarget.textContent = listName;
        this.panelContentTarget.innerHTML = template.innerHTML;

        // Show the panel
        this.panelTarget.classList.remove('hidden');
    }

    close() {
        this.panelTarget.classList.add('hidden');
    }

    // Stop links and buttons from triggering the "view" action
    prevent(event) {
        event.stopPropagation();
    }
}