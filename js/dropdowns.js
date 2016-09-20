var lastMenuId = 0;
var timer;
var mSheets = new Array();
var currZ = 100;
var mReady = false;
var mLeftPos = 326;

function getLeftPos(obj)
{
	var res = 0;
	while (obj)
	{
		res += obj.offsetLeft;
		obj = obj.offsetParent;
	}
	return res;
}

function getTopPos(obj)
{
	var res = 0;
	while (obj)
	{
		res += obj.offsetTop;
		obj = obj.offsetParent;
	}
	return res;
}

function MenuLink(textVal, linkVal, subVal){
	this.text = textVal;
	this.action = linkVal;
	this.submenu = subVal;
}

function menuHideAll(){
	for (var c in mSheets) 
		if (typeof (mSheets[c].hide) == 'function')	
			mSheets[c].hide();	
}

function menuHideTimerSet(){
	timer = window.setTimeout(menuHideAll, 100);
}

function menuHideTimerReset(){
	if (timer) window.clearTimeout(timer);
}

function menuAddLink(textVal, linkVal){
	this.links[this.links.length] = new MenuLink(textVal, linkVal, null);
}

function menuAddSubmenu(textVal, linkVal){
	this.links[this.links.length] = new MenuLink(textVal, linkVal, new MenuSheet(this));
}

function menuShow(leftVal, topVal){
	this.block.style.left = leftVal + "px";
	this.block.style.top = topVal + "px";
	this.block.style.display = "block";
	$(this.block).bgiframe();
}

function menuHide(){
	this.hideCh();
	this.block.style.display = "none";
}

function menuFlip(leftVal, topVal){
	var disp = this.block.style.display;
	if (disp == "none") this.show(leftVal, topVal);
	else this.hide();
}

function menuHideCh(){
	for (var c in this.links){
		curLink = this.links[c];
		if (curLink.submenu) curLink.submenu.hide();
	}
}

function menuCreate(path){
	var res = "<div class=\"menu-sh\" onmouseout=\"menuHideTimerSet()\" onmouseover=\"menuHideTimerReset()\"><table cellpadding=\"0\" cellspacing=\"0\" class=\"tab-menu-sh\">";
	var curLink;
	var newPath;
	if (path == null) path = "mSheets[" + this.id + "]";
	for (var c in this.links){
 		if (typeof this.links[c].text == 'undefined') continue;
		curLink = this.links[c];
		res += "<tr><td class=\"blk-menu-sh";
		if (curLink.submenu) res += " blk-menu-arr";
		res += "\" onmouseover=\"$(this).addClass('blk-menu-sh-act";
		if (curLink.submenu) res += " blk-menu-arr-act";
		res += "'); ";
		res += path + ".hideCh()";
		if (curLink.submenu){
			newPath = path + ".links[" + c + "].submenu";
			res += "; " + newPath + ".show(getLeftPos(this) + this.offsetWidth, getTopPos(this))";
			curLink.submenu.create(newPath);
		}
		res += "\" onmouseout=\"$(this).removeClass('blk-menu-sh-act";
		if (curLink.submenu) res += " blk-menu-arr";
		res += "')\" onclick=\"gotoURL('"+curLink.action+"')\" nowrap=\"nowrap\">" + curLink.text + "</td></tr>";
	}
	res += "</table></div>";
	this.block.innerHTML = res;

}

function MenuSheet(parentObj){
	this.links = new Array();
	this.addLink = menuAddLink;
	this.addSubmenu = menuAddSubmenu;
	this.create = menuCreate;
	this.show = menuShow;
	this.hide = menuHide;
	this.flip = menuFlip;
	this.hideCh = menuHideCh;
	this.id = lastMenuId;
	lastMenuId++;
	this.parent = parentObj; 
	this.block = document.createElement("DIV");
	this.block.className = "blk-menu";
	this.block.style.position = "absolute";
	this.block.style.display = "none";
	this.block.style.zIndex = currZ;
	currZ++;
	this.block.id = "ms" + this.id;
	document.body.appendChild(this.block);
}

function showMenu(objVal, numVal){
	if (mReady){
		menuHideAll();
		mSheets[numVal].show(getLeftPos(objVal)-16, getTopPos(objVal) + 22);
		menuHideTimerReset();
	}
}

function hideMenu(objVal, numVal){
	if (mReady){
		menuHideTimerSet();
	}
}

function gotoURL(url) {
	location.href = url;
}