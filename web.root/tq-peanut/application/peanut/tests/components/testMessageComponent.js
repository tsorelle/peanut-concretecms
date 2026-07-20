var Peanut;
(function (Peanut) {
    class testMessageComponent {
        constructor() {
            this.message = ko.observable('I am a late bound component');
        }
    }
    Peanut.testMessageComponent = testMessageComponent;
})(Peanut || (Peanut = {}));
//# sourceMappingURL=testMessageComponent.js.map