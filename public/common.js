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
    temp[key] = clone(obj[key]);
  return temp;
}


//-------------------------------------------------------------------------------
//                                   DOM Reading
//-------------------------------------------------------------------------------

function byId(id, ignoreError = false)
// Returns one element by ID or null
// id: string
// ignoreError: boolean, default false
{
  var element = document.getElementById(id);
  if (element == null && !ignoreError)
    console.log('Element with ID "' + id + '" does not exist.');
  return element;
}


// Returns all elements by name
function byName(name)
{
  var elements = document.getElementsByName(name);
  // Don't use elements 'length' and 'item':
  var list = [];
  for (var i = 0; i < elements.length; i++)
    list.push(elements[i]);
  return list;
}


function getSubElements(element, nodeName = null)
// Returns all direct sub-elements, optional filter by node name
{
  if (isNotNull(nodeName))
    nodeName = nodeName.toUpperCase();
  var elements = element.childNodes;
  // Don't use elements 'length' and 'item':
  var list = [];
  for (var i = 0; i < elements.length; i++) {
    if (!isNotNull(nodeName) || elements[i].nodeName == nodeName)
      list.push(elements[i]);
  }
  return list;
}


function getSubElementByNodeName(element, nodeName, index = 0)
// Returns one direct sub-element by node name
{
  nodeName = nodeName.toUpperCase();
  var elements = getSubElements(element);
  for (var i in elements)
    if (elements[i].nodeName == nodeName) {
      if (index == 0)
        return elements[i];
      index = index - 1;
    }
}


function getSubElementsRecursive(element, nodeName = null)
// Returns all (indirect) sub-element, optional filter by node name
{
  if (isNotNull(nodeName))
    nodeName = nodeName.toUpperCase();
  var result = [];
  var Sub = getSubElements(element);
  for (var i in Sub) {
    if (!isNotNull(nodeName) || Sub[i].nodeName == nodeName)
      result.push(Sub[i]);
    var toMerge = getSubElementsRecursive(Sub[i], nodeName);
    for (var j in toMerge)
      result.push(toMerge[j]);
  }
  return result;
}


function byClass(ClassName)
// Returns all elements by class
{
  var elements = document.getElementsByTagName('*');
  var list = [];
  for (var i = 0; i < elements.length; i++) { // Don't use elements 'length' and 'item':
    if (elements[i].classList.contains(ClassName))
      list.push(elements[i]);
  }
  return list;
}


function getSubElementByNodeNameRecursive(element, nodeName, index = 0)
// Returns one (indirect) sub-element by node name
{
  nodeName = nodeName.toUpperCase();
  var elements = getSubElementsRecursive(element);
  for (var i in elements) {
    if (elements[i].nodeName == nodeName) {
      if (index == 0)
        return elements[i];
      index = index - 1;
    }
  }
}


//-------------------------------------------------------------------------------
//                                   DOM Modification
//-------------------------------------------------------------------------------

function deleteElement(element)
// Remove a DOM element
{
  return element.parentNode.removeChild(element);
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

  var formData = new FormData();
  var a = getSubElementsRecursive(event.target, 'INPUT');
  a.forEach(function(entry) {
    if (entry.name) {
      if (entry.type == 'checkbox')
        formData.append(entry.name, entry.checked);
      else
        formData.append(entry.name, entry.value);
    }
  });
  var a = getSubElementsRecursive(event.target, 'TEXTAREA');
  a.forEach(function(entry) {
    if (entry.name)
      formData.append(entry.name, entry.value);
  });
  var a = getSubElementsRecursive(event.target, 'SELECT');
  a.forEach(function(entry) {
    if (entry.name)
      formData.append(entry.name, entry.value);
  });

  setFormEnabled(event.target, false);

  var req = new XMLHttpRequest();
  req.open('POST', event.target.action, true);
  req.onload = function() {
    if (req.readyState == 4) {
      if (req.status === 200)
        eval(req.responseText);
      else
        showErrorMsg('Beim Ausführen der Aktion ist etwas schiefgegangen. \nHTTP-Status: ' + req.status);
      setTimeout(function() {
        setFormEnabled(event.target, true);
      }, 500);
    }
  };
  req.onerror = function() {
    showErrorMsg('Verbindung zum Server fehlgeschlagen.');
    setTimeout(function() {
      setFormEnabled(event.target, true);
    }, 500);
  };
  req.send(formData);
}


function setFormEnabled(form, enabled)
// Enable/disable interaction of all form elements
{
  var elements = getSubElementsRecursive(form, 'INPUT');
  elements = elements.concat(getSubElementsRecursive(form, 'TEXTAREA'));
  elements = elements.concat(getSubElementsRecursive(form, 'SELECT'));
  elements = elements.concat(getSubElementsRecursive(form, 'BUTTON'));
  elements.forEach(function(entry) {
    if (entry.nodeName == 'BUTTON' || entry.nodeName == 'SELECT' || entry.type == 'checkbox')
      entry.disabled = !enabled;
    else
      entry.readOnly = !enabled;
  });
}


function openUrlInNewTabOnMiddleClick(event, url)
{
  if (event.which == 2)
    window.open(url);
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

function trim(str)
// Remove whitespace from beginning and end of a string
{
  if (typeof(str) != 'string')
    return str;
  return str.replace(/^\s+|\s+$/g, '');
}


function isNumeric(str)
// Returns true if 'str' is a valid number
{
  return !isNaN(parseFloat(str)) && isFinite(str);
}


function encodeHtml(str)
// Convert plain text to HTML
{
  if (typeof(str) != 'string')
    return str;
  str = str.replace(/&/g, '&amp;');
  str = str.replace(/</g, '&lt;');
  str = str.replace(/>/g, '&gt;');
  return str;
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


function getCookie(name)
{
  var s = ' ' + document.cookie;
  var i = s.indexOf(' ' + name + '=');
  if (i == -1)
    return null;
  i = s.indexOf('=', i) + 1;
  var j = s.indexOf(';', i);
  if (j == -1)
    j = s.length;
  return unescape(s.substring(i, j));
}


function setCookie(name, value, days = 365)
{
  var date = new Date();
  date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
    document.cookie = name + '=' + value + '; expires=' + date.toGMTString() + '; path=/';
}


//-------------------------------------------------------------------------------
//                                User Interaction
//-------------------------------------------------------------------------------


function focusFirstChildInputNode(parent)
{
  const elements = getSubElementsRecursive(parent, 'INPUT');
  for (var i in elements) {
    const element = elements[i];
    if (element.type == 'checkbox')
      continue;
    element.focus();
    element.setSelectionRange(0, 9999);
    break;
  }
}

function showErrorMsg(msg)
{
  alert('Fehler: ' + msg);
}


//-------------------------------------------------------------------------------
//                                  Auto Reload
//-------------------------------------------------------------------------------

var autoReloadTimer;
var autoReloadEnabled;

function enableAutoReload(checkImmediately = false)
{
  const indicator = byId('autoReloadIndicator');
  if (!indicator)
    return;
  indicator.style.display = 'inline';
  updateAutoReloadIndicator(true);
  startAutoReloadTimer(checkImmediately);
  autoReloadEnabled = true;
}

function disableAutoReload()
{
  autoReloadEnabled = false;
  stopAutoReload();
}

function stopAutoReload()
{
  const indicator = byId('autoReloadIndicator');
  if (indicator)
    indicator.style.display = 'none';
  if (autoReloadTimer)
    clearTimeout(autoReloadTimer);
}

function updateAutoReloadIndicator(online)
{
  const indicator = byId('autoReloadIndicator');
  if (online) {
    indicator.title = 'Webseite wird automatisch aktualisiert';
    indicator.classList.remove('offline');
  } else {
    indicator.title = 'Server ist nicht mehr erreichbar';
    indicator.classList.add('offline');
  }
}

function startAutoReloadTimer(checkImmediately = false)
{
  var timerIntervalMilliSec = 10000;
  if (checkImmediately)
    timerIntervalMilliSec = 10;

  autoReloadTimer = setTimeout(function() {
    const indicator = byId('autoReloadIndicator');
    indicator.classList.add('active');

    autoReloadTimer = setTimeout(function() {
      indicator.classList.remove('active');
    }, 500);

    var formData = new FormData();
    formData.append('autoReloadHash', byId('autoReloadHash').value);
    let req = new XMLHttpRequest();
    req.open('POST', '?a=autoReloadCheck');
    req.onload = function() {
      if (req.readyState == 4) {
        if (req.status === 200)
        {
          eval(req.responseText);
          updateAutoReloadIndicator(true);
        }
        else
          updateAutoReloadIndicator(false);
      }
    };
    req.onerror = function() {
      updateAutoReloadIndicator(false);
    };
    req.send(formData);

    startAutoReloadTimer();
  }, timerIntervalMilliSec);
}

window.addEventListener('visibilitychange', function() {
  if (autoReloadEnabled && !document.hidden)
    enableAutoReload(true);
  else
    stopAutoReload();
});
