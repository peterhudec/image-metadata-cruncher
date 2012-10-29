<?php
$s = array(
	'abc {def:ijk} bca',
	'abc {def:i.j#k} bca',
	'abc {abc:def.ijk#lmn} def',
	'abc {abc:def>} abc',
	'abc {abc:def>ijk} abc',
	'abc {abc:def>ijk>} abc',
	'abc {abc:def>ijk>lmn} abc',
	'abc {abc:def|efg:hij} abc',
	'abc {abc:def|efg:hij|klm:nop} abc',
	'abc {abc:def | efg:hij | klm:nop} abc',
	'abc {abc:def | efg:hij | klm:nop } abc',
	
	'abc {abc:def } abc',
	'abc {abc:def @ "suc" } abc',
	'abc {abc:def @ "suc\" } abc',
	'abc {abc:def @ "suc"suc" } abc',
	'abc {abc:def @ "su\"suc" } abc',
	'abc {abc:def @ "su \"suc \" bla" } abc',
	
	
	'abc {abc:def @ "suc" } abc',
	'abc {abc:def @ "suc" % "def" } abc',
	'abc {abc:def @ "suc" # "del" } abc',
	'abc {abc:def @ "suc" % "def" # "del" } abc',
	'abc {abc:def % "def" # "del" } abc',
	'abc {abc:def # "del" } abc',
	'abc {abc:def @ " $" } abc',
	'abc {abc:def % "def" } abc',
);

$key = '
	[\w]+ # caterory prefix
	: # colon
	[\w.#]+ # keyword first part
	(?: # zero or more keyword parts
		> # part delimiter
		[\w.#]+ # part
	)*
';

$keys = '
	'.$key.' # at least one key
	(?: # zero or more additional keys
		\s* # space
		\| # colon delimiter
		\s* # space
		'.$key.' # key
	)*
';

$pattern = '/
	{
	\s*
	(?P<keywords>'.$keys.')
	\s*
	(?: # success
		@ # identifier
		\s* # space
		" # opening quote
		(?P<success> # capture value
			(?: # must contain
				\\\\" # either escaped doublequote \"
				| # or
				[^"] # any non doublequote character
			)* # zero or more times
		)
		" # closing quote
		\s*
	)?
	(?: # default
		% # identifier
		\s* # space
		" # opening quote
		(?P<default> # capture value
			(?: # must contain
				\\\\" # either escaped doublequote \"
				| # or
				[^"] # any non doublequote character
			)* # zero or more times
		)
		" # closing quote
		\s*
	)?
	(?: # delimiter
		\# # identifier
		\s* # space
		" # opening quote
		(?P<delimiter> # capture value
			(?: # must contain
				\\\\" # either escaped doublequote \"
				| # or
				[^"] # any non doublequote character
			)* # zero or more times
		)
		" # closing quote
		\s*
	)?
	}
/x';

function validate_tag ( $tag, $pattern ) {
	preg_match( $pattern, $tag, $match );
	if ( isset( $match[0] ) ) {
		return $match[0];
	}
}

foreach ($s as $value) {
	print "\n\n-----------------------\n";
	print $value."\n";
	print_r(validate_tag($value, $pattern));
}

?>