<?php
class BaseElement {
    function __construct($data) {
        foreach ($data as $key => $value) {
            $this->{$key} = $value;
        }
    }
}