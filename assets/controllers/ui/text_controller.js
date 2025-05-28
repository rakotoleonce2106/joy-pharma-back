import {Controller} from "@hotwired/stimulus";

export default class UIText extends Controller {
    setText(content) {
        this.element.innerText = content;
    }

    getText() {
        return this.element.innerText;
    }
}