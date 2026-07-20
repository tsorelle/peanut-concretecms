var Peanut;
(function (Peanut) {
    class testFormComponent {
        constructor() {
            this.userInput = ko.observable('');
            this.message = ko.observable('');
            this.setMessage = (s) => {
                this.message("Message from main vm: " + s);
            };
        }
    }
    Peanut.testFormComponent = testFormComponent;
})(Peanut || (Peanut = {}));
//# sourceMappingURL=testFormComponent.js.map