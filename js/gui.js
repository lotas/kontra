function updateTooltip(elm) {
	var i=elm.selectedIndex*7;	
	$('#boxtxt').html(
		'Рейтинг:' + questions[i+4] + ' | Cредний бал: ' + questions[i+6] + ' |  Ответов: ' + questions[i+3] + 
		' | Автор: ' + questions[i+5]  + "<br/>" + limitStrLen(questions[i+1], 100));	
}

function limitStrLen(str, len) {
	return (str.length > len) ? str.substr(0, len) + '...' : str;
}

function askQuestion() {
	$('#askquestion').jqmShow();	
	return false;
}

function closeAskQuestion() {
	$('#askquestion').jqmHide();	
	return false;
}

function showFilter() {
	$('#quicksearch').jqmShow();
	return false;
}

function submitBug() {
	$('#submitBug').jqmShow();
	return false;
}

function closeSubmitBug() {
	$('#submitBug').jqmHide();
	return false;
}

function gotol(elm) {
	if (elm.value != '')
		location.href = elm.value;
}
