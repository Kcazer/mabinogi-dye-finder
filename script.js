(function () {
    // Variables
    var oldColor, newColor;
    var inputText = document.forms[0].color;
    var inputColor = inputText.nextElementSibling;
    // Function
    var UpdateColor = function () {
        if (!/^#?[0-9A-F-a-f]{6}$/.test(this.value)) return;
        newColor = '#' + this.value.substr(-6);
        if (newColor != oldColor) {
            oldColor = newColor;
            inputText.value = newColor;
            inputColor.value = newColor;
        }
    };
    // Events
    inputText.addEventListener('keyup', UpdateColor, false);
    inputText.addEventListener('change', UpdateColor, false);
    inputColor.addEventListener('change', UpdateColor, false);
})();
