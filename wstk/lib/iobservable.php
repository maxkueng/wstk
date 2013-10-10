<?php

interface IWeHookable {

	public function hook ($hook, IWeObserver $object, $callback);
	public function invoke ();

}
