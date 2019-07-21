/*
 * 手机端动态分配rem
 */
window.onload = function(){
	// 获取视窗宽度
	var htmlWidth = document.documentElement.clientWidth || document.body.clientWidth;

	// 获取视窗高度
	var htmlDom = document.getElementsByTagName('html')[0];
	
	// 动态设置html font-size值
	htmlDom.style.fontSize = htmlWidth / 10 + 'px';
};