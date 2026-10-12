export default class Notice {
    constructor({data}) { this.data = data; }
    render() { const element = document.createElement('p'); element.textContent = this.data.text; return element; }
    save() { return this.data; }
}
