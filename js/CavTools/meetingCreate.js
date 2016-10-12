function meetingOption() {
    var list = document.getElementById("list");
    var value = list.options[list.selectedIndex].value;

    if (value === 'A'){
        document.getElementById('createMeeting').style.display = 'none';
        document.getElementById('createUsingTemplate').style.display = 'block';
    } else if (value === 'B'){
        document.getElementById('createMeeting').style.display = 'block';
        document.getElementById('createUsingTemplate').style.display = 'none';
    }
}
