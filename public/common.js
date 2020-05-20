"use strict";

//-------------------------------------------------------------------------------
//                                     General
//-------------------------------------------------------------------------------


function isNotNull(Var)
{
	return typeof(Var) != 'undefined' && Var !== null;
}

function clone(obj)
{
  if (obj === null || typeof(obj) !== 'object')
    return obj;
  var temp = obj.constructor(); // give temp the original obj's constructor
  for (var key in obj)
    temp[key] = Clone(obj[key]);
  return temp;
}


//-------------------------------------------------------------------------------
//                                   DOM Reading
//-------------------------------------------------------------------------------

function byId(ID, IgnoreError)
// Returns one element by ID or null
// ID: string
// IgnoreError: boolean, default false
{
	if (!isNotNull(IgnoreError))
		IgnoreError = false;
	var El = document.getElementById(ID);
	if (El == null && !IgnoreError)
		console.log('Element with ID "' + ID + '" does not exist');
	return El;
}


// Returns all elements by name
function byName(Name)
{
	var Elements = document.getElementsByName(Name);
	// Don't use elements 'length' and 'item':
	var List = [];
	for (var i = 0; i < Elements.length; i++)
		List.push(Elements[i]);
	return List;
}


function getSubElements(Element, NodeName)
// Returns all direct sub-elements, optional filter by node name
{
	if (isNotNull(NodeName))
		NodeName = NodeName.toUpperCase();
	var Elements = Element.childNodes;
	// Don't use elements 'length' and 'item':
	var List = [];
	for (var i = 0; i < Elements.length; i++) {
		if (!isNotNull(NodeName) || Elements[i].nodeName == NodeName)
			List.push(Elements[i]);
  }
	return List;
}



function getSubElementByNodeName(Element, NodeName, Index)
// Returns one direct sub-element by node name
{
	if (!isNotNull(Index))
		Index = 0;
	NodeName = NodeName.toUpperCase();
	var Elements = getSubElements(Element);
	for (var i in Elements)
		if (Elements[i].nodeName == NodeName) {
			if (Index == 0)
				return Elements[i];
			Index = Index - 1;
		}
}


function getSubElementsRecursive(Element, NodeName)
// Returns all (indirect) sub-element, optional filter by node name
{
	if (isNotNull(NodeName))
		NodeName = NodeName.toUpperCase();
	var result = [];
	var Sub = getSubElements(Element);
	for (var i in Sub) {
		if (!isNotNull(NodeName) || Sub[i].nodeName == NodeName)
			result.push(Sub[i]);
		var toMerge = getSubElementsRecursive(Sub[i], NodeName);
		for (var j in toMerge)
			result.push(toMerge[j]);
	}
	return result;
}



function byClass(ClassName)
// Returns all elements by class
{
	var Elements = document.getElementsByTagName('*');
	var List = [];
	for (var i = 0; i < Elements.length; i++) { // Don't use elements 'length' and 'item':
		if (IsClass(Elements[i], ClassName))
			List.push(Elements[i]);
  }
	return List;
}


function getSubElementByNodeNameRecursive(Element, NodeName, Index)
// Returns one (indirect) sub-element by node name
{
	if (!isNotNull(Index))
		Index = 0;
	NodeName = NodeName.toUpperCase();
	var Elements = getSubElementsRecursive(Element);
	for (var i in Elements) {
		if (Elements[i].nodeName == NodeName) {
			if (Index == 0)
				return Elements[i];
			Index = Index - 1;
		}
  }
}


//-------------------------------------------------------------------------------
//                                   DOM Modification
//-------------------------------------------------------------------------------

function deleteElement(El)
// Remove a DOM element
{
	return El.parentNode.removeChild(El);
}


function insertElementAfter(referenceNode, newNode)
{
	referenceNode.parentNode.insertBefore(newNode, referenceNode.nextSibling);
}


//-------------------------------------------------------------------------------
//                                   Formulars
//-------------------------------------------------------------------------------

function postForm(event)
// Sends a Ajax POST request with all 'input', 'textarea' and 'select' fields.
{
  if (!isNotNull(event))
    return;

	event.preventDefault();
	setFormEnabled(event.target, false);

	var formData = new FormData();

	var a = getSubElementsRecursive(event.target, 'input');
	a.forEach(function(entry) {
		if (entry.name && !entry.disabled)
			formData.append(entry.name, entry.value);
	});
	var a = getSubElementsRecursive(event.target, 'textarea');
	a.forEach(function(entry) {
		if (entry.name && !entry.disabled)
			formData.append(entry.name, entry.value);
	});
	var a = getSubElementsRecursive(event.target, 'select');
	a.forEach(function(entry) {
		if (entry.name && !entry.disabled)
			formData.append(entry.name, entry.value);
	});

	var req = new XMLHttpRequest();
	req.open('POST', event.target.action, true);
	req.onload = function() {
    if (req.readyState == 4) {
      if (req.status === 200)
        eval(req.responseText);
      else
        showErrorMsg('Beim Ausführen der Aktion ist etwas schiefgegangen. \nHTTP-Status: ' + req.status);
      window.setTimeout(function() {
        setFormEnabled(event.target, true);
      }, 500);
    }
	};
  req.onerror = function() {
    showErrorMsg('Verbindung zum Server fehlgeschlagen.');
    window.setTimeout(function() {
      setFormEnabled(event.target, true);
    }, 500);
  };
	req.send(formData);
}


function setFormEnabled(form, enabled)
// Disable buttons to prevent resubmit
{
	setElementsReadOnly(getSubElementsRecursive(form, 'input'), !enabled);
	setElementsReadOnly(getSubElementsRecursive(form, 'textarea'), !enabled);
	setElementsEnabled(getSubElementsRecursive(form, 'button'), enabled);
}


function setElementsReadOnly(elements, readOnly)
{
	elements.forEach(function(entry) {
    entry.readOnly = readOnly;
	});
}


function setElementsEnabled(elements, enabled)
{
	elements.forEach(function(entry) {
    entry.disabled = !enabled;
	});
}


//-------------------------------------------------------------------------------
//                                  Error Reporting
//-------------------------------------------------------------------------------

var errors = 0;
window.onerror = function(message, file, line, col, error) {
	if (errors == 0)
		showErrorMsg('Es ist ein JavaScript-Fehler aufgetreten.');
	errors++;
	console.log(message);
	if (file)
		console.log('Source:' + file + ':' + line + ':' + col);
	if (error) {
		console.log('Stack trace:');
		console.log(error.stack);
	}
	return false; // run default error handler
};

function raiseError(msg) {
	window.onerror(msg);
}



//-------------------------------------------------------------------------------
//                                  String Functions
//-------------------------------------------------------------------------------

function trim(Str)
// Remove whitespace from beginning and end of a string
{
	if (typeof(Str) != 'string')
		return Str;
	return Str.replace(/^\s+|\s+$/g, '');
}


function isNumeric(str)
// Returns true if 'str' is a valid number
{
	return !isNaN(parseFloat(str)) && isFinite(str);
}


function ParseInt(str)
// Returns true if 'str' is a valid integer
{
	if (isNumeric(str))
		return parseInt(str);
	return null;
}


function encodeHtml(Str)
// Convert plain text to HTML
{
	if (typeof(Str) != 'string')
		return Str;
	Str = Str.replace(/&/g, '&amp;');
	Str = Str.replace(/</g, '&lt;');
	Str = Str.replace(/>/g, '&gt;');
	return Str;
}



//-------------------------------------------------------------------------------
//                                    Cookies
//-------------------------------------------------------------------------------

function deleteAllCookies()
{
  var cookies = document.cookie.split(';');
  for (var i in cookies) {
    var eqPos = cookies[i].indexOf('=');
    var name = eqPos > -1 ? cookies[i].substr(0, eqPos) : cookies[i];
    document.cookie = name + '=;expires=Thu, 01 Jan 1970 00:00:00 GMT';
  }
}

function getCookie(Name)
{
	var s = ' ' + document.cookie;
	var i = s.indexOf(' ' + Name + '=');
	if (i == -1)
		return null;
	i = s.indexOf('=', i) + 1;
	var j = s.indexOf(';', i);
	if (j == -1)
		j = s.length;
	return unescape(s.substring(i, j));
}

function setCookie(Name, Value, Days)
{
	if (!isNotNull(Days))
		Days = 365;
	var date = new Date();
	date.setTime(date.getTime() + (Days * 24 * 60 * 60 * 1000));
    document.cookie = Name + '=' + Value + '; expires=' + date.toGMTString() + '; path=/';
}


//-------------------------------------------------------------------------------
//                                User Interaction
//-------------------------------------------------------------------------------


function focusFirstChildInputNode(parent)
{
  const child = getSubElementByNodeNameRecursive(parent, 'input', 0);
  if (child) {
    child.focus();
    child.setSelectionRange(0, 9999);
  }
}

function showErrorMsg(msg)
{
  alert('Fehler: ' + msg);
}
