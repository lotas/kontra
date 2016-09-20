var questions = new Array( "--" );

var top10 = document.getElementById('top10select');

for (var i=0; questions[i] != "--"; i+=7) {
	var opt = new Option('['+questions[i+3]+"] ("+ questions[i+4] + " / " + questions[i+6] + ") " + questions[i+1].substr(0,100).replace(/&quot;/g, '"'));
	opt.value = questions[i];
	opt.style.backgroundColor = questions[i+2];
	
	top10.options[top10.options.length] = opt;
}