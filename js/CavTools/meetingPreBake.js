function templateOption() {
    var list = document.getElementById("list");
    var value = list.options[list.selectedIndex].value;

    if (value === 'A'){
        document.getElementById('createTemplate').style.display = 'block';
        document.getElementById('showTemplates').style.display = 'none';
    } else if (value === 'B'){
        document.getElementById('createTemplate').style.display = 'none';
        document.getElementById('showTemplates').style.display = 'block';
    }
}
