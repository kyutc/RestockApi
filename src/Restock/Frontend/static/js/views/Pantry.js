import AbstractView from "./AbstractView.js";

export default class extends AbstractView {
    constructor(params) {
        super(params);
        this.setTitle("Pantry");
    }

    async getHtml() {
        return `
            <h1></h1>
            <p></p>
        `;
    }
}