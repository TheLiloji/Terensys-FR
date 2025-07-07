<?php

namespace Spip\Core\Tests;

use PHPUnit\Framework\TestCase;


class SafeHtmlTest extends TestCase {

	protected static $safehtml;

	public static function setUpBeforeClass(): void{
		find_in_path("inc/texte_mini.php",'',true);

		// chercher la fonction si elle n'existe pas
		if (!function_exists(static::$safehtml='safehtml')){
			find_in_path("inc/filtres.php",'',true);
			static::$safehtml = chercher_filtre(static::$safehtml);
		}
	}


	/**
	 * @dataProvider providerXSS
	 */
	public function testXSS($expected, $source) {
		$safehtml = static::$safehtml;
		$actual = $safehtml($source);
		if (is_string($expected) and strpos($expected, "&quot;") !== false) {
			$expected = [$expected, str_replace('&quot;', '"', $expected)];
		}
		if (is_array($expected)) {
			$this->assertContains($actual, $expected);
		} else {
			$this->assertEquals($expected, $actual);
		}
	}


	public function providerXSS() {
		$essais = array (
  0 =>
  array (
    0 => '',
    1 => '',
  ),
  1 =>
  array (
    0 => '0',
    1 => '0',
  ),
  2 =>
  array (
    0 => [
		'Un texte avec des <a href="http://spip.net">liens</a> [Article 1->art1] [spip->https://www.spip.net] https://www.spip.net',
		'Un texte avec des <a href="http://spip.net">liens</a> [Article 1-&gt;art1] [spip-&gt;https://www.spip.net] https://www.spip.net',
	],
    1 => 'Un texte avec des <a href="http://spip.net">liens</a> [Article 1->art1] [spip->https://www.spip.net] https://www.spip.net',
  ),
  3 =>
  array (
    0 => 'Un texte avec des entit&eacute;s &amp;&lt;&gt;&quot;',
    1 => 'Un texte avec des entit&eacute;s &amp;&lt;&gt;&quot;',
  ),
  4 =>
  array (
    0 => 'Un texte avec des entit&amp;eacute;s echap&amp;eacute; &amp;amp;&amp;lt;&amp;gt;&amp;quot;',
    1 => 'Un texte avec des entit&amp;eacute;s echap&amp;eacute; &amp;amp;&amp;lt;&amp;gt;&amp;quot;',
  ),
  5 =>
  array (
    0 => 'Un texte avec des entit&#233;s num&#233;riques &#38;&#60;&#62;&quot;',
    1 => 'Un texte avec des entit&#233;s num&#233;riques &#38;&#60;&#62;&quot;',
  ),
  6 =>
  array (
    0 => 'Un texte avec des entit&amp;#233;s num&amp;#233;riques echap&amp;#233;es &amp;#38;&amp;#60;&amp;#62;&amp;quot;',
    1 => 'Un texte avec des entit&amp;#233;s num&amp;#233;riques echap&amp;#233;es &amp;#38;&amp;#60;&amp;#62;&amp;quot;',
  ),
  7 =>
  array (
    0 => [
		'Un texte sans entites &&lt;>"\'',
		'Un texte sans entites &amp;&lt;&gt;"\'',
	],
    1 => 'Un texte sans entites &<>"\'',
  ),
  8 =>
  array (
    0 => '{{{Des raccourcis}}} {italique} {{gras}} <code>du code</code>',
    1 => '{{{Des raccourcis}}} {italique} {{gras}} <code>du code</code>',
  ),
  9 =>
  array (
    0 => [
		'Un modele https://www.spip.net]>',
		'Un modele https://www.spip.net]&gt;',
	],
    1 => 'Un modele <modeleinexistant|lien=[->https://www.spip.net]>',
  ),
  10 =>
  array (
    0 => 'Un texte avec des retour
a la ligne et meme des

paragraphes',
    1 => 'Un texte avec des retour
a la ligne et meme des

paragraphes',
  ),
  11 =>
  array (
    0 => [
	    '\';alert(String.fromCharCode(88,83,83))//\\\';alert(String.fromCharCode(88,83,83))//";alert(String.fromCharCode(88,83,83))//\\";alert(String.fromCharCode(88,83,83))//-->">\'><code class="echappe-js">&lt;SCRIPT&gt;alert(String.fromCharCode(88,83,83))&lt;/SCRIPT&gt;</code>=&{}',
	    '\';alert(String.fromCharCode(88,83,83))//\\\';alert(String.fromCharCode(88,83,83))//";alert(String.fromCharCode(88,83,83))//\\";alert(String.fromCharCode(88,83,83))//--&gt;"&gt;\'&gt;<code class="echappe-js">&lt;SCRIPT&gt;alert(String.fromCharCode(88,83,83))&lt;/SCRIPT&gt;</code>=&amp;{}',
    ],
    1 => '\';alert(String.fromCharCode(88,83,83))//\\\';alert(String.fromCharCode(88,83,83))//";alert(String.fromCharCode(88,83,83))//\\";alert(String.fromCharCode(88,83,83))//--></SCRIPT>">\'><SCRIPT>alert(String.fromCharCode(88,83,83))</SCRIPT>=&{}',
  ),
  12 =>
  array (
    0 => [
		'\'\';!--"<xss>=&{()}</xss>',
		'\'\';!--"=&amp;{()}',
	],
    1 => '\'\';!--"<XSS>=&{()}',
  ),
  13 =>
  array (
    0 => '<code class="echappe-js">&lt;SCRIPT&gt;alert(\'XSS\')&lt;/SCRIPT&gt;</code>',
    1 => '<SCRIPT>alert(\'XSS\')</SCRIPT>',
  ),
  14 =>
  array (
    0 => '<code class="echappe-js">&lt;SCRIPT SRC=http://ha.ckers.org/xss.js&gt;&lt;/SCRIPT&gt;</code>',
    1 => '<SCRIPT SRC=http://ha.ckers.org/xss.js></SCRIPT>',
  ),
  15 =>
  array (
    0 => '<code class="echappe-js">&lt;SCRIPT&gt;alert(String.fromCharCode(88,83,83))&lt;/SCRIPT&gt;</code>',
    1 => '<SCRIPT>alert(String.fromCharCode(88,83,83))</SCRIPT>',
  ),
  16 =>
  array (
    0 => [
		'&lt;base HREF="javascript:alert(\'XSS\');//">',
		'&lt;base HREF="javascript:alert(\'XSS\');//"&gt;',
	],
    1 => '<BASE HREF="javascript:alert(\'XSS\');//">',
  ),
  17 =>
  array (
    0 => '<code class="echappe-js">&lt;BGSOUND SRC="javascript:alert(\'XSS\');"&gt;</code>',
    1 => '<BGSOUND SRC="javascript:alert(\'XSS\');">',
  ),
  18 =>
  array (
    0 => '<code class="echappe-js">&lt;BODY BACKGROUND="javascript:alert(\'XSS\');"&gt;</code>',
    1 => '<BODY BACKGROUND="javascript:alert(\'XSS\');">',
  ),
  19 =>
  array (
    0 => '<code class="echappe-js">&lt;BODY ONLOAD=alert(\'XSS\')&gt;</code>',
    1 => '<BODY ONLOAD=alert(\'XSS\')>',
  ),
  20 =>
  array (
    0 => '<div></div>',
    1 => '<DIV STYLE="background-image: url(javascript:alert(\'XSS\'))">',
  ),
  21 =>
  array (
    0 => '<div></div>',
    1 => '<DIV STYLE="background-image: url(&#1;javascript:alert(\'XSS\'))">',
  ),
  22 =>
  array (
    0 => '<div></div>',
    1 => '<DIV STYLE="width: expression(alert(\'XSS\'));">',
  ),
  23 =>
  array (
    0 => '',
    1 => '<FRAMESET><FRAME SRC="javascript:alert(\'XSS\');"></FRAMESET>',
  ),
  24 =>
  array (
    0 => '<code class="echappe-js">&lt;IFRAME SRC="javascript:alert(\'XSS\');"&gt;&lt;/IFRAME&gt;</code>',
    1 => '<IFRAME SRC="javascript:alert(\'XSS\');"></IFRAME>',
  ),
  25 =>
  array (
    0 => [
		'<input type="IMAGE" />',
		'<input type="image" />',
		'<input type="image" alt="image">',
	],
    1 => '<INPUT TYPE="IMAGE" SRC="javascript:alert(\'XSS\');">',
  ),
  26 =>
  array (
    0 => '<code class="echappe-js">&lt;IMG SRC="javascript:alert(\'XSS\');"&gt;</code>',
    1 => '<IMG SRC="javascript:alert(\'XSS\');">',
  ),
  27 =>
  array (
    0 => '<code class="echappe-js">&lt;IMG SRC=javascript:alert(\'XSS\')&gt;</code>',
    1 => '<IMG SRC=javascript:alert(\'XSS\')>',
  ),
  28 =>
  array (
    0 => '<code class="echappe-js">&lt;IMG DYNSRC="javascript:alert(\'XSS\');"&gt;</code>',
    1 => '<IMG DYNSRC="javascript:alert(\'XSS\');">',
  ),
  29 =>
  array (
    0 => '<code class="echappe-js">&lt;IMG LOWSRC="javascript:alert(\'XSS\');"&gt;</code>',
    1 => '<IMG LOWSRC="javascript:alert(\'XSS\');">',
  ),
  30 =>
  array (
    0 => [
	    '<img src="http://www.thesiteyouareon.com/somecommand.php?somevariables=maliciouscode" />',
	    '<img src="http://www.thesiteyouareon.com/somecommand.php?somevariables=maliciouscode" alt="somecommand.php?somevariables=maliciouscode" />',
	    '<img src="http://www.thesiteyouareon.com/somecommand.php?somevariables=maliciouscode" alt="somecommand.php?somevariables=maliciouscode">',
    ],
    1 => '<IMG SRC="http://www.thesiteyouareon.com/somecommand.php?somevariables=maliciouscode">',
  ),
  31 =>
  array (
    0 => [
	    'exp/*<xss style="noxss:noxss(&quot;*/pression(alert(&quot;XSS&quot;))"></xss>',
	    'exp/*',
    ],
    1 => 'exp/*<XSS STYLE=\'no\\xss:noxss("*//*");
xss:&#101;x&#x2F;*XSS*//*/*/pression(alert("XSS"))\'>',
  ),
  32 =>
  array (
    0 => '<ul><li>XSS</li></ul>',
    1 => '<STYLE>li {list-style-image: url("javascript:alert(\'XSS\')");}</STYLE><UL><LI>XSS',
  ),
  33 =>
  array (
    0 => '<code class="echappe-js">&lt;IMG SRC=\'vbscript:msgbox("XSS")\'&gt;</code>',
    1 => '<IMG SRC=\'vbscript:msgbox("XSS")\'>',
  ),
  34 =>
  array (
    0 => '',
    1 => '<LAYER SRC="http://ha.ckers.org/scriptlet.html"></LAYER>',
  ),
  35 =>
  array (
    0 => '<code class="echappe-js">&lt;IMG SRC="livescript:[code]"&gt;</code>',
    1 => '<IMG SRC="livescript:[code]">',
  ),
  36 =>
  array (
    0 => '�script�alert(�XSS�)�/script�',
    1 => '�script�alert(�XSS�)�/script�',
  ),
  37 =>
  array (
    0 => '<code class="echappe-js">&lt;META HTTP-EQUIV="refresh" CONTENT="0;url=javascript:alert(\'XSS\');"&gt;</code>',
    1 => '<META HTTP-EQUIV="refresh" CONTENT="0;url=javascript:alert(\'XSS\');">',
  ),
  38 =>
  array (
    0 => '<code class="echappe-js">&lt;META HTTP-EQUIV="refresh" CONTENT="0;url=data:text/html;base64,PHNjcmlwdD5hbGVydCgnWFNTJyk8L3NjcmlwdD4K"&gt;</code>',
    1 => '<META HTTP-EQUIV="refresh" CONTENT="0;url=data:text/html;base64,PHNjcmlwdD5hbGVydCgnWFNTJyk8L3NjcmlwdD4K">',
  ),
  39 =>
  array (
    0 => '<code class="echappe-js">&lt;META HTTP-EQUIV="refresh" CONTENT="0; URL=http://;URL=javascript:alert(\'XSS\');"&gt;</code>',
    1 => '<META HTTP-EQUIV="refresh" CONTENT="0; URL=http://;URL=javascript:alert(\'XSS\');">',
  ),
  40 =>
  array (
    0 => [
		'<img />',
		'',
	],
    1 => '<IMG SRC="mocha:[code]">',
  ),
  41 =>
  array (
    0 => '',
    1 => '<OBJECT TYPE="text/x-scriptlet" DATA="http://ha.ckers.org/scriptlet.html"></OBJECT>',
  ),
  42 =>
  array (
    0 => '<code class="echappe-js">&lt;OBJECT classid=clsid:ae24fdae-03c6-11d1-8b76-0080c744f389&gt;&lt;param name=url value=javascript:alert(\'XSS\')&gt;&lt;/OBJECT&gt;</code>',
    1 => '<OBJECT classid=clsid:ae24fdae-03c6-11d1-8b76-0080c744f389><param name=url value=javascript:alert(\'XSS\')></OBJECT>',
  ),
  43 =>
  array (
    0 => '',
    1 => '<EMBED SRC="http://ha.ckers.org/xss.swf" AllowScriptAccess="always"></EMBED>',
  ),
  44 =>
  array (
    0 => '',
    1 => '<STYLE TYPE="text/javascript">alert(\'XSS\');</STYLE>',
  ),
  45 =>
  array (
    0 => [
		'<img />',
		'',
	],
    1 => '<IMG STYLE="xss:expr/*XSS*/ession(alert(\'XSS\'))">',
  ),
  46 =>
  array (
    0 => [
		'<xss></xss>',
		'',
	],
    1 => '<XSS STYLE="xss:expression(alert(\'XSS\'))">',
  ),
  47 =>
  array (
    0 => '<a class="XSS"></a>',
    1 => '<STYLE>.XSS{background-image:url("javascript:alert(\'XSS\')");}</STYLE><A CLASS=XSS></A>',
  ),
  48 =>
  array (
    0 => '',
    1 => '<STYLE type="text/css">BODY{background:url("javascript:alert(\'XSS\')")}</STYLE>',
  ),
  49 =>
  array (
    0 => '',
    1 => '<LINK REL="stylesheet" HREF="javascript:alert(\'XSS\');">',
  ),
  50 =>
  array (
    0 => '',
    1 => '<LINK REL="stylesheet" HREF="http://ha.ckers.org/xss.css">',
  ),
  51 =>
  array (
    0 => '',
    1 => '<STYLE>@import\'http://ha.ckers.org/xss.css\';</STYLE>',
  ),
  52 =>
  array (
    0 => '',
    1 => '<META HTTP-EQUIV="Link" Content="<http://ha.ckers.org/xss.css>; REL=stylesheet">',
  ),
  53 =>
  array (
    0 => '',
    1 => '<STYLE>BODY{-moz-binding:url("http://ha.ckers.org/xssmoz.xml#xss")}</STYLE>',
  ),
  54 =>
  array (
    0 => [
		'<table></table>',
		'',
	],
    1 => '<TABLE BACKGROUND="javascript:alert(\'XSS\')"></TABLE>',
  ),
  55 =>
  array (
    0 => [
		'<table><td></td></table>',
		'<table></table>',
	    '',
	],
    1 => '<TABLE><TD BACKGROUND="javascript:alert(\'XSS\')"></TD></TABLE>',
  ),
  56 =>
  array (
    0 => [
		'
&lt;?import namespace="xss" implementation="http://ha.ckers.org/xss.htc">
XSS

',
		'
&lt;?import namespace="xss" implementation="http://ha.ckers.org/xss.htc"&gt;
XSS',
	],
    1 => '<HTML xmlns:xss>
<?import namespace="xss" implementation="http://ha.ckers.org/xss.htc">
<xss:xss>XSS</xss:xss>

</HTML>',
  ),
  57 =>
  array (
    0 => [
		'<span></span>',
		'&lt;IMG SRC="javascript:alert(\'XSS\');"&gt;

<span></span>',
	],
    1 => '<XML ID=I><X><C><![CDATA[<IMG SRC="javas]]><![CDATA[cript:alert(\'XSS\');">]]>

</C></X></xml><SPAN DATASRC=#I DATAFLD=C DATAFORMATAS=HTML>',
  ),
  58 =>
  array (
    0 => ['

<span></span>',
	    '<i><b><img src="javas" alt="javas&lt;!-- --&gt;cript:alert(\'XSS\')" /></b></i><span></span>',
	    '<i><b><img src="javas" alt="javas&lt;!-- --&gt;cript:alert(\'XSS\')"></b></i><span></span>',
	],
    1 => '<XML ID="xss"><I><B><IMG SRC="javas<!-- -->cript:alert(\'XSS\')"></B></I></XML>

<SPAN DATASRC="#xss" DATAFLD="B" DATAFORMATAS="HTML"></SPAN>',
  ),
  59 =>
  array (
    0 => [
		'
<span></span>',
		'<span></span>',
	],
    1 => '<XML SRC="http://ha.ckers.org/xsstest.xml" ID=I></XML>
<SPAN DATASRC=#I DATAFLD=C DATAFORMATAS=HTML></SPAN>',
  ),
  60 =>
  array (
    0 => [
	    '
&lt;?xml:namespace prefix="t" ns="urn:schemas-microsoft-com:time">

&lt;?import namespace="t" implementation="#default#time2">
&lt;SCRIPT DEFER&gt;alert(\'XSS\')&lt;/SCRIPT&gt;"> ',
	    '
&lt;?xml:namespace prefix="t" ns="urn:schemas-microsoft-com:time"&gt;

&lt;?import namespace="t" implementation="#default#time2"&gt;
&lt;SCRIPT DEFER&gt;alert(\'XSS\')&lt;/SCRIPT&gt;"&gt; ',
    ],
    1 => '<HTML><BODY>
<?xml:namespace prefix="t" ns="urn:schemas-microsoft-com:time">

<?import namespace="t" implementation="#default#time2">
<t:set attributeName="innerHTML" to="XSS<SCRIPT DEFER>alert(\'XSS\')</SCRIPT>"> </BODY></HTML>',
  ),
  61 =>
  array (
    0 => '',
    1 => '<!--[if gte IE 4]>
<SCRIPT>alert(\'XSS\');</SCRIPT>
<![endif]-->',
  ),
  62 =>
  array (
    0 => [
		'&lt;SCRIPT&gt;alert(\'XSS\')&lt;/SCRIPT&gt;">',
		'&lt;SCRIPT&gt;alert(\'XSS\')&lt;/SCRIPT&gt;"&gt;',
	],
    1 => '<META HTTP-EQUIV="Set-Cookie" Content="USERID=<SCRIPT>alert(\'XSS\')</SCRIPT>">',
  ),
  63 =>
  array (
    0 => [
		'<xss></xss>',
		'',
	],
    1 => '<XSS STYLE="behavior: url(http://ha.ckers.org/xss.htc);">',
  ),
  64 =>
  array (
    0 => '<code class="echappe-js">&lt;SCRIPT SRC=&quot;http://ha.ckers.org/xss.jpg&quot;&gt;&lt;/SCRIPT&gt;</code>',
    1 => '<SCRIPT SRC="http://ha.ckers.org/xss.jpg"></SCRIPT>',
  ),
  65 =>
  array (
    0 => '',
    1 => '<!--#exec cmd="/bin/echo \'<SCRIPT SRC\'"--><!--#exec cmd="/bin/echo \'=http://ha.ckers.org/xss.js></SCRIPT>\'"-->',
  ),
  66 =>
  array (
    0 => [
		'&lt;? echo(\'alert("XSS")\'); ?>',
		'&lt;? echo(\'alert("XSS")\'); ?&gt;',
	],
    1 => '<? echo(\'<SCR)\';
echo(\'IPT>alert("XSS")</SCRIPT>\'); ?>',
  ),
  67 =>
  array (
    0 => [
		'<br size="&{alert(\'XSS\')}" />',
		'<br />',
		'<br>',
	],
    1 => '<BR SIZE="&{alert(\'XSS\')}">',
  ),
  68 =>
  array (
    0 => [
		'&lt;
%3C
&lt
&lt;
&LT
&LT;
&#60
&#060
&#0060

&#00060
&#000060
&#0000060
&#60;
&#060;
&#0060;
&#00060;
&#000060;
&#0000060;
&#x3c
&#x03c
&#x003c
&#x0003c
&#x00003c
&#x000003c
&#x3c;
&#x03c;

&#x003c;
&#x0003c;
&#x00003c;
&#x000003c;
&#X3c
&#X03c
&#X003c
&#X0003c
&#X00003c
&#X000003c
&#X3c;
&#X03c;
&#X003c;
&#X0003c;
&#X00003c;
&#X000003c;
&#x3C

&#x03C
&#x003C
&#x0003C
&#x00003C
&#x000003C
&#x3C;
&#x03C;
&#x003C;
&#x0003C;
&#x00003C;
&#x000003C;
&#X3C
&#X03C
&#X003C
&#X0003C
&#X00003C
&#X000003C

&#X3C;
&#X03C;
&#X003C;
&#X0003C;
&#X00003C;
&#X000003C;
\\x3c
\\x3C
\\u003c
\\u003C',
		'&lt;
%3C
&amp;lt
&lt;
&amp;LT
&amp;LT;
&lt;
&lt;
&lt;

&lt;
&lt;
&lt;
&lt;
&lt;
&lt;
&lt;
&lt;
&lt;
&lt;
&lt;
&lt;
&lt;
&lt;
&lt;
&lt;
&lt;

&lt;
&lt;
&lt;
&lt;
&lt;
&lt;
&lt;
&lt;
&lt;
&lt;
&lt;
&lt;
&lt;
&lt;
&lt;
&lt;
&lt;

&lt;
&lt;
&lt;
&lt;
&lt;
&lt;
&lt;
&lt;
&lt;
&lt;
&lt;
&lt;
&lt;
&lt;
&lt;
&lt;
&lt;

&lt;
&lt;
&lt;
&lt;
&lt;
&lt;
\\x3c
\\x3C
\\u003c
\\u003C',
],
    1 => '<
%3C
&lt
&lt;
&LT
&LT;
&#60
&#060
&#0060

&#00060
&#000060
&#0000060
&#60;
&#060;
&#0060;
&#00060;
&#000060;
&#0000060;
&#x3c
&#x03c
&#x003c
&#x0003c
&#x00003c
&#x000003c
&#x3c;
&#x03c;

&#x003c;
&#x0003c;
&#x00003c;
&#x000003c;
&#X3c
&#X03c
&#X003c
&#X0003c
&#X00003c
&#X000003c
&#X3c;
&#X03c;
&#X003c;
&#X0003c;
&#X00003c;
&#X000003c;
&#x3C

&#x03C
&#x003C
&#x0003C
&#x00003C
&#x000003C
&#x3C;
&#x03C;
&#x003C;
&#x0003C;
&#x00003C;
&#x000003C;
&#X3C
&#X03C
&#X003C
&#X0003C
&#X00003C
&#X000003C

&#X3C;
&#X03C;
&#X003C;
&#X0003C;
&#X00003C;
&#X000003C;
\\x3c
\\x3C
\\u003c
\\u003C',
  ),
  69 =>
  array (
    0 => '<code class="echappe-js">&lt;IMG SRC=JaVaScRiPt:alert(\'XSS\')&gt;</code>',
    1 => '<IMG SRC=JaVaScRiPt:alert(\'XSS\')>',
  ),
  70 =>
  array (
    0 => '<code class="echappe-js">&lt;IMG SRC=javascript:alert(&amp;quot;XSS&amp;quot;)&gt;</code>',
    1 => '<IMG SRC=javascript:alert(&quot;XSS&quot;)>',
  ),
  71 =>
  array (
    0 => '<code class="echappe-js">&lt;IMG SRC=`javascript:alert("RSnake says, \'XSS\'")`&gt;</code>',
    1 => '<IMG SRC=`javascript:alert("RSnake says, \'XSS\'")`>',
  ),
  72 =>
  array (
    0 => '<code class="echappe-js">&lt;IMG SRC=javascript:alert(String.fromCharCode(88,83,83))&gt;</code>',
    1 => '<IMG SRC=javascript:alert(String.fromCharCode(88,83,83))>',
  ),
  73 =>
  array (
    0 => [
		'<img />',
	    ''
	],
    1 => '<IMG SRC=&#106;&#97;&#118;&#97;&#115;&#99;&#114;&#105;&#112;&#116;&#58;&#97;&#108;&#101;&#114;&#116;&#40;&#39;&#88;&#83;&#83;&#39;&#41;>',
  ),
  74 =>
  array (
    0 => [
		'<img />',
		'',
	],
    1 => '<IMG SRC=&#0000106&#0000097&#0000118&#0000097&#0000115&#0000099&#0000114&#0000105&#0000112&#0000116&#0000058&#0000097&#0000108&#0000101&#0000114&#0000116&#0000040&#0000039&#0000088&#0000083&#0000083&#0000039&#0000041>',
  ),
  75 =>
  array (
    0 => [
		'<div style="background-image:00750072006C0028\'006a006100760061007300630072006900700074003a0061006c0065007200740028.10270058.1053005300270029\'0029"></div>',
	    '<div></div>'
	],
    1 => '<DIV STYLE="background-image:\\0075\\0072\\006C\\0028\'\\006a\\0061\\0076\\0061\\0073\\0063\\0072\\0069\\0070\\0074\\003a\\0061\\006c\\0065\\0072\\0074\\0028.1027\\0058.1053\\0053\\0027\\0029\'\\0029">',
  ),
  76 =>
  array (
    0 => [
		'<img />',
	    '',
	],
    1 => '<IMG SRC=&#x6A&#x61&#x76&#x61&#x73&#x63&#x72&#x69&#x70&#x74&#x3A&#x61&#x6C&#x65&#x72&#x74&#x28&#x27&#x58&#x53&#x53&#x27&#x29>',
  ),
  77 =>
  array (
    0 => [
		' ',
	    '+ADw-SCRIPT+AD4-alert(\'XSS\');+ADw-/SCRIPT+AD4-',
	],
    1 => '<HEAD><META HTTP-EQUIV="CONTENT-TYPE" CONTENT="text/html; charset=UTF-7"> </HEAD>+ADw-SCRIPT+AD4-alert(\'XSS\');+ADw-/SCRIPT+AD4-',
  ),
  78 =>
  array (
    0 => '\\";alert(\'XSS\');//',
    1 => '\\";alert(\'XSS\');//',
  ),
  79 =>
  array (
    0 => '<code class="echappe-js">&lt;SCRIPT&gt;alert(&quot;XSS&quot;);&lt;/SCRIPT&gt;</code>',
    1 => '</TITLE><SCRIPT>alert("XSS");</SCRIPT>',
  ),
  80 =>
  array (
    0 => '',
    1 => '<STYLE>@im\\port\'\\ja\\vasc\\ript:alert("XSS")\';</STYLE>',
  ),
  81 =>
  array (
    0 => '<code class="echappe-js">&lt;IMG SRC="jav	ascript:alert(\'XSS\');"&gt;</code>',
    1 => '<IMG SRC="jav	ascript:alert(\'XSS\');">',
  ),
  82 =>
  array (
    0 => '<code class="echappe-js">&lt;IMG SRC="jav&amp;#x09;ascript:alert(\'XSS\');"&gt;</code>',
    1 => '<IMG SRC="jav&#x09;ascript:alert(\'XSS\');">',
  ),
  83 =>
  array (
    0 => '<code class="echappe-js">&lt;IMG SRC="jav&amp;#x0A;ascript:alert(\'XSS\');"&gt;</code>',
    1 => '<IMG SRC="jav&#x0A;ascript:alert(\'XSS\');">',
  ),
  84 =>
  array (
    0 => '<code class="echappe-js">&lt;IMG SRC="jav&amp;#x0D;ascript:alert(\'XSS\');"&gt;</code>',
    1 => '<IMG SRC="jav&#x0D;ascript:alert(\'XSS\');">',
  ),
  85 =>
  array (
    0 => [
		'<img />
',
	    '<img src="j%20a%20v%20a%20s%20c%20r%20i%20p%20t%20%3A%20a%20l%20e%20r%20t%20(%20\'%20X%20S%20S%20\'%20)" alt="j a v a s c r i p t : a l e r t ( \' X S S \' )" />',
	    '<img src="j%20a%20v%20a%20s%20c%20r%20i%20p%20t%20%3A%20a%20l%20e%20r%20t%20(%20\'%20X%20S%20S%20\'%20)" alt="j a v a s c r i p t : a l e r t ( \' X S S \' )">',
	],
    1 => '<IMG
SRC
=
"
j
a
v
a
s
c
r
i
p
t
:
a
l
e
r
t
(
\'
X
S
S
\'
)
"
>
',
  ),
  86 =>
  array (
    0 => [
		'<code class="echappe-js">&lt;IMG SRC=java' . "\0" . 'script:alert("XSS")&gt;</code>',
		'<code class="echappe-js">&lt;IMG SRC=javascript:alert("XSS")&gt;</code>',
	],
    1 => '<IMG SRC=java' . "\0" . 'script:alert("XSS")>',
  ),
  87 =>
  array (
    0 => [
		'&alert("XSS")',
		'&amp;',
	],
    1 => '&<SCR' . "\0" . 'IPT>alert("XSS")</SCR' . "\0" . 'IPT>',
  ),
  88 =>
  array (
    0 => '<code class="echappe-js">&lt;IMG SRC=" &amp;#14;  javascript:alert(\'XSS\');"&gt;</code>',
    1 => '<IMG SRC=" &#14;  javascript:alert(\'XSS\');">',
  ),
  89 =>
  array (
    0 => '<code class="echappe-js">&lt;SCRIPT/XSS SRC=&quot;http://ha.ckers.org/xss.js&quot;&gt;&lt;/SCRIPT&gt;</code>',
    1 => '<SCRIPT/XSS SRC="http://ha.ckers.org/xss.js"></SCRIPT>',
  ),
  90 =>
  array (
    0 => [
		'|\\]^`=alert("XSS")>',
		'',
	],
    1 => '<BODY onload!#$%&()*~+-_.,:;?@[/|\\]^`=alert("XSS")>',
  ),
  91 =>
  array (
    0 => '<code class="echappe-js">&lt;SCRIPT SRC=http://ha.ckers.org/xss.js</code>',
    1 => '<SCRIPT SRC=http://ha.ckers.org/xss.js',
  ),
  92 =>
  array (
    0 => '<code class="echappe-js">&lt;SCRIPT SRC=//ha.ckers.org/.j&gt;</code>',
    1 => '<SCRIPT SRC=//ha.ckers.org/.j>',
  ),
  93 =>
  array (
    0 => '<code class="echappe-js">&lt;IMG SRC="javascript:alert(\'XSS\')"</code>',
    1 => '<IMG SRC="javascript:alert(\'XSS\')"',
  ),
  94 =>
  array (
    0 => '',
    1 => '<IFRAME SRC=http://ha.ckers.org/scriptlet.html <',
  ),
  95 =>
  array (
    0 => '&lt;<code class="echappe-js">&lt;SCRIPT&gt;alert(&quot;XSS&quot;);//&lt;&lt;/SCRIPT&gt;</code>',
    1 => '<<SCRIPT>alert("XSS");//<</SCRIPT>',
  ),
  96 =>
  array (
    0 => [
		'<img /><code class="echappe-js">&lt;SCRIPT&gt;alert(&quot;XSS&quot;)&lt;/SCRIPT&gt;</code>">',
		'<code class="echappe-js">&lt;SCRIPT&gt;alert("XSS")&lt;/SCRIPT&gt;</code>"&gt;',
	],
    1 => '<IMG """><SCRIPT>alert("XSS")</SCRIPT>">',
  ),
  97 =>
  array (
    0 => [
		'<code class="echappe-js">&lt;SCRIPT&gt;a=/XSS/<br />
alert(a.source)&lt;/SCRIPT&gt;</code>',
		'<code class="echappe-js">&lt;SCRIPT&gt;a=/XSS/<br>
alert(a.source)&lt;/SCRIPT&gt;</code>',
	],
    1 => '<SCRIPT>a=/XSS/
alert(a.source)</SCRIPT>',
  ),
  98 =>
  array (
    0 => '<code class="echappe-js">&lt;SCRIPT a=&quot;&gt;&quot; SRC=&quot;http://ha.ckers.org/xss.js&quot;&gt;&lt;/SCRIPT&gt;</code>',
    1 => '<SCRIPT a=">" SRC="http://ha.ckers.org/xss.js"></SCRIPT>',
  ),
  99 =>
  array (
    0 => '<code class="echappe-js">&lt;SCRIPT =&quot;blah&quot; SRC=&quot;http://ha.ckers.org/xss.js&quot;&gt;&lt;/SCRIPT&gt;</code>',
    1 => '<SCRIPT ="blah" SRC="http://ha.ckers.org/xss.js"></SCRIPT>',
  ),
  100 =>
  array (
    0 => '<code class="echappe-js">&lt;SCRIPT a=&quot;blah&quot; \'\' SRC=&quot;http://ha.ckers.org/xss.js&quot;&gt;&lt;/SCRIPT&gt;</code>',
    1 => '<SCRIPT a="blah" \'\' SRC="http://ha.ckers.org/xss.js"></SCRIPT>',
  ),
  101 =>
  array (
    0 => '<code class="echappe-js">&lt;SCRIPT &quot;a=\'&gt;\'&quot; SRC=&quot;http://ha.ckers.org/xss.js&quot;&gt;&lt;/SCRIPT&gt;</code>',
    1 => '<SCRIPT "a=\'>\'" SRC="http://ha.ckers.org/xss.js"></SCRIPT>',
  ),
  102 =>
  array (
    0 => '<code class="echappe-js">&lt;SCRIPT a=`&gt;` SRC=&quot;http://ha.ckers.org/xss.js&quot;&gt;&lt;/SCRIPT&gt;</code>',
    1 => '<SCRIPT a=`>` SRC="http://ha.ckers.org/xss.js"></SCRIPT>',
  ),
  103 =>
  array (
    0 => [
	    '<code class="echappe-js">&lt;SCRIPT&gt;document.write(&quot;&lt;SCRI&quot;);&lt;/SCRIPT&gt;</code>PT SRC="http://ha.ckers.org/xss.js">',
	    '<code class="echappe-js">&lt;SCRIPT&gt;document.write("&lt;SCRI");&lt;/SCRIPT&gt;</code>PT SRC="http://ha.ckers.org/xss.js"&gt;',
    ],
    1 => '<SCRIPT>document.write("<SCRI");</SCRIPT>PT SRC="http://ha.ckers.org/xss.js"></SCRIPT>',
  ),
  104 =>
  array (
    0 => '<code class="echappe-js">&lt;SCRIPT a=&quot;&gt;\'&gt;&quot; SRC=&quot;http://ha.ckers.org/xss.js&quot;&gt;&lt;/SCRIPT&gt;</code>',
    1 => '<SCRIPT a=">\'>" SRC="http://ha.ckers.org/xss.js"></SCRIPT>',
  ),
  105 =>
  array (
    0 => '<a href="http://66.102.7.147/">XSS</a>',
    1 => '<A HREF="http://66.102.7.147/">XSS</A>',
  ),
  106 =>
  array (
    0 => [
		'<a href="http://%77%77%77%2E%67%6F%6F%67%6C%65%2E%63%6F%6D">XSS</a>',
		'<a href="http://www.google.com">XSS</a>',
	],
    1 => '<A HREF="http://%77%77%77%2E%67%6F%6F%67%6C%65%2E%63%6F%6D">XSS</A>',
  ),
  107 =>
  array (
    0 => '<a href="http://1113982867/">XSS</a>',
    1 => '<A HREF="http://1113982867/">XSS</A>',
  ),
  108 =>
  array (
    0 => '<a href="http://0x42.0x0000066.0x7.0x93/">XSS</a>',
    1 => '<A HREF="http://0x42.0x0000066.0x7.0x93/">XSS</A>',
  ),
  109 =>
  array (
    0 => '<a href="http://0102.0146.0007.00000223/">XSS</a>',
    1 => '<A HREF="http://0102.0146.0007.00000223/">XSS</A>',
  ),
  110 =>
  array (
    0 => [
		'<a>XSS</a>',
		'<a href="h%20tt%20p%3A//6%206.000146.0x7.147/">XSS</a>',
	],
    1 => '<A HREF="h
tt	p://6&#09;6.000146.0x7.147/">XSS</A>',
  ),
  111 =>
  array (
    0 => '<a href="//www.google.com/">XSS</a>',
    1 => '<A HREF="//www.google.com/">XSS</A>',
  ),
  112 =>
  array (
    0 => '<a href="//google">XSS</a>',
    1 => '<A HREF="//google">XSS</A>',
  ),
  113 =>
  array (
    0 => [
		'<a href="http://ha.ckers.org@google">XSS</a>',
		'<a href="http://google">XSS</a>',
	],
    1 => '<A HREF="http://ha.ckers.org@google">XSS</A>',
  ),
  114 =>
  array (
    0 => [
		'<a href="http://google:ha.ckers.org">XSS</a>',
		'<a href="http://google">XSS</a>',
	],
    1 => '<A HREF="http://google:ha.ckers.org">XSS</A>',
  ),
  115 =>
  array (
    0 => '<a href="http://google.com/">XSS</a>',
    1 => '<A HREF="http://google.com/">XSS</A>',
  ),
  116 =>
  array (
    0 => '<a href="http://www.google.com./">XSS</a>',
    1 => '<A HREF="http://www.google.com./">XSS</A>',
  ),
  117 =>
  array (
    0 => '<a>XSS</a>',
    1 => '<A HREF="javascript:document.location=\'http://www.google.com/\'">XSS</A>',
  ),
  118 =>
  array (
    0 => [
		'<a href="http://www.gohttp://www.google.com/ogle.com/">XSS</a>', // safehtml
		'<a href="http://www.gohttp//www.google.com/ogle.com/">XSS</a>', // htmlpurifier
		],
    1 => '<A HREF="http://www.gohttp://www.google.com/ogle.com/">XSS</A>',
  ),
  119 =>
  array (
    0 => '<span class="montant" data-montant-nombre="100" data-montant-devise="EUR"></span>',
    1 => '<span class="montant" data-montant-nombre="100" data-montant-devise="EUR">',
  ),

  120 =>
  array (
    0 => '<span class="montant" itemscope itemtype="https://schema.org/PriceSpecification" data-montant-nombre="30" data-montant-devise="EUR"><span class="montant__inner" itemprop="price" content="30">30,00 <span class="montant__devise" itemprop="priceCurrency" content="EUR">EUR</span></span></span>',
    1 => '<span class="montant" itemscope itemtype="https://schema.org/PriceSpecification" data-montant-nombre="30" data-montant-devise="EUR"><span class="montant__inner" itemprop="price" content="30">30,00 <span class="montant__devise" itemprop="priceCurrency" content="EUR">EUR</span></span></span>',
  ),


  121 =>
  array (
    0 => '<span class="montant" data-montant-nombre="30" data-montant-devise="EUR">30,00 <span class="montant__devise">EUR</span><meta itemprop="price" content="30" /><meta itemprop="priceCurrency" content="EUR"></span>',
    1 => '<span class="montant" data-montant-nombre="30" data-montant-devise="EUR">30,00 <span class="montant__devise">EUR</span><meta itemprop="price" content="30" /><meta itemprop="priceCurrency" content="EUR"></span>',
  ),

  122 =>
  array (
    0 => '<span class="montant" data-montant-nombre="30" data-montant-devise="EUR">30,00 <span class="montant__devise">EUR</span><meta itemprop="price" content="30" /><meta itemprop="priceCurrency" content="EUR"></span>',
    1 => '<span class="montant" data-montant-nombre="30" data-montant-devise="EUR">30,00 <span class="montant__devise">EUR</span><meta itemprop="price" content="30" /><meta itemprop="priceCurrency" content="EUR"></span>',
  ),

  123 =>
  array (
    0 => '<span class="montant" data-montant-nombre="30" data-montant-devise="EUR">30,00 <span class="montant__devise">EUR</span><meta itemprop="price" content="30" /><meta itemprop="priceCurrency" content="EUR" /></span>',
    1 => '<span class="montant" data-montant-nombre="30" data-montant-devise="EUR">30,00 <span class="montant__devise">EUR</span><meta itemprop="price" content="30" /><meta http-equiv="Link" itemprop="priceCurrency" content="EUR"></span>',
  ),

);

		return $essais;
	}
}
