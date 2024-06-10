<?php

namespace ByJG\Util;

enum XmlUtilEnum: int
{
    case XMLUTIL_OPT_DONT_PRESERVE_WHITESPACE = 0x01;
    case XMLUTIL_OPT_FORMAT_OUTPUT = 0x02;
    case XMLUTIL_OPT_DONT_FIX_AMPERSAND = 0x04;

}
