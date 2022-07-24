<?php
namespace de\interaapps\ulole\orm\helper;

class MySQLOperator {
    public const ADD = "+";
    public const SUBTRACT = "-";
    public const MULTIPLY = "*";
    public const DIVIDE = "/";
    public const MODULO = "%";

    public const BIT_AND = "&";
    public const BIT_OR = "|";
    public const BIT_EXCL_OR = "^";

    public const EQUALS = "=";
    public const GREATER_THAN = ">";
    public const LESS_THAN = "<";
    public const GREATER_THAN_OR_EQUALS = ">=";
    public const LESS_THAN_OR_EQUALS = "<=";
    public const NOT_EQUAL = "<>";


    public const ADD_EQUALS = "+=";
    public const SUBTRACT_EQUALS = "-=";
    public const MULTIPLY_EQUALS = "*=";
    public const DIVIDE_EQUALS = "/=";
    public const MODULO_EQUALS = "%=";
    public const BIT_EXCL_EQUALS = "^-=";
    public const BIT_OR_EQUALS = "|*=";

    public const LIKE = "LIKE";
    public const ALL = "ALL";
    public const AND = "AND";
    public const ANY = "ANY";
    public const BETWEEN = "BETWEEN";
    public const EXISTS = "EXISTS";
    public const IN = "IN";
    public const NOT = "NOT";
    public const OR = "OR";
    public const SOME = "SOME";

    public const WILDCARD = "%";
    public const WILDCARD_CHAR = "_";
}
