<?php

function newIdField()
{
  $field = [];
  $field['type'] = 'integer';
  $field['name'] = 'id';
  $field['title'] = 'Nr.';
  $field['editable'] = false;
  $field['visibleInList'] = false;
  return $field;
}


function newTextField($name, $title, $editable = true, $visibleInList = true)
{
  $field = [];
  $field['type'] = 'text';
  $field['name'] = $name;
  $field['title'] = $title;
  $field['editable'] = $editable;
  $field['visibleInList'] = $visibleInList;
  return $field;
}


function newTextAreaField($name, $title, $editable = true, $visibleInList = true)
{
  $field = [];
  $field['type'] = 'textArea';
  $field['name'] = $name;
  $field['title'] = $title;
  $field['editable'] = $editable;
  $field['visibleInList'] = $visibleInList;
  return $field;
}


function newIntegerField($name, $title, $editable = true, $visibleInList = true)
{
  $field = [];
  $field['type'] = 'integer';
  $field['name'] = $name;
  $field['title'] = $title;
  $field['editable'] = $editable;
  $field['visibleInList'] = $visibleInList;
  return $field;
}


function newTimestampField($name, $title, $editable = true, $visibleInList = true, $precision = 'minute')
{
  $field = [];
  $field['type'] = 'timestamp';
  $field['name'] = $name;
  $field['title'] = $title;
  $field['editable'] = $editable;
  $field['visibleInList'] = $visibleInList;
  $field['precision'] = $precision;
  return $field;
}


function newLinkAction($page, $title, $idParamName, $visibleInList = true)
{
  $action = [];
  $action['type'] = 'link';
  $action['page'] = $page;
  $action['title'] = $title;
  $action['idParamName'] = $idParamName;
  $action['visibleInList'] = $visibleInList;
  return $action;
}


function renderItemTable($items, $fields, $actions = [])
{
  echo html_open('table', ['class' => 'border']);
  echo html_open('tr');
  foreach ($fields as $field)
  {
    echo html_node('th', html_encode($field['title']));
  }
  if (count($actions) > 0)
    echo html_node('th', 'Aktionen');
  echo html_close('tr');
  foreach ($items as $item)
  {
    echo html_open('tr', ['class' => array_value($item, 'class')]);
    foreach ($fields as $field)
    {
      $value = $item[$field['name']];
      if ($value === null)
        echo html_node('td', '–', ['class' => 'null']);
      else
      {
        $type = $field['type'];
        $classes = [];
        $classes[] = $type;
        $classes[] = $field['name'];
        echo html_open('td', ['class' => implode(' ', $classes)]);
        if ($type == 'text')
          echo html_encode($value);
        else if ($type == 'textArea')
          echo html_encode($value);
        else if ($type == 'integer')
          echo html_encode($value);
        else if ($type == 'timestamp')
          echo formatTimestampLocalShort($value, $field['precision']);
        echo html_close('td');
      }
    }
    if (count($actions) > 0)
    {
      echo html_open('td', ['class' => 'actions']);
      foreach ($actions as $action)
      {
        $url = '?p=' . $action['page'] . '&' . $action['idParamName'] . '=' . $item['id'];
        echo html_a($url, html_encode($action['title']));
      }
      echo html_close('td');
    }
    echo html_close('tr');
  }
  echo html_close('table');
}
