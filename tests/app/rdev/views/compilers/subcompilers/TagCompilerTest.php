<?php
/**
 * Copyright (C) 2015 David Young
 * 
 * Tests the tag sub-compiler
 */
namespace RDev\Views\Compilers\SubCompilers;
use RDev\HTTP\Requests\Request;
use RDev\Tests\Mocks\User;
use RDev\Tests\Views\Compilers\Tests\Compiler as CompilerTest;
use RDev\Views\Compilers\ViewCompilerException;
use RDev\Views\Template;

class TagCompilerTest extends CompilerTest
{
    /** @var TagCompiler The sub-compiler to test */
    private $subCompiler = null;

    /**
     * Sets up the tests
     */
    public function setUp()
    {
        parent::setUp();

        $this->subCompiler = new TagCompiler($this->compiler, $this->xssFilter);
    }

    /**
     * Tests calling a function on a variable
     */
    public function testCallingFunctionOnVariable()
    {
        // Test object
        $this->template->setVar("request", Request::createFromGlobals());
        $this->template->setContents('{{!$request->isPath("/foo/.*", true) ? \' class="current"\' : ""!}}');
        $this->assertEquals("", $this->subCompiler->compile($this->template, $this->template->getContents()));
        // Test class
        $this->template->setContents(
            '{{!RDev\Tests\Views\Compilers\SubCompilers\Mocks\ClassWithStaticMethod::foo() == "bar" ? "y" : "n"!}}'
        );
        $this->assertEquals("y", $this->subCompiler->compile($this->template, $this->template->getContents()));
    }

    /**
     * Tests compiling an array variable inside tags
     */
    public function testCompilingArrayVariableInsideTags()
    {
        $delimiters = [
            [
                Template::DEFAULT_OPEN_ESCAPED_TAG_DELIMITER,
                Template::DEFAULT_CLOSE_ESCAPED_TAG_DELIMITER
            ],
            [
                Template::DEFAULT_OPEN_UNESCAPED_TAG_DELIMITER,
                Template::DEFAULT_CLOSE_UNESCAPED_TAG_DELIMITER
            ]
        ];
        $templateContents = '<?php foreach(["foo" => ["bar", "a&w"]] as $v): ?>%s$v[1]%s<?php endforeach; ?>';
        $this->template->setContents(sprintf($templateContents, $delimiters[0][0], $delimiters[0][1]));
        $this->assertTrue(
            $this->stringsWithEncodedCharactersEqual(
                "a&amp;w",
                $this->subCompiler->compile($this->template, $this->template->getContents())
            )
        );
        $this->template->setContents(sprintf($templateContents, $delimiters[1][0], $delimiters[1][1]));
        $this->assertEquals("a&w", $this->subCompiler->compile($this->template, $this->template->getContents()));
    }

    /**
     * Tests compiling an escaped tag whose value is an unescaped tag
     */
    public function testCompilingEscapedTagWhoseValueIsUnescapedTag()
    {
        // Order here is important
        // We're testing setting the inner-most tag first, and then the outer tag
        $this->template->setContents("{{!content!}}");
        $this->template->setTag("message", "world");
        $this->template->setTag("content", "Hello, {{message}}!");
        $this->assertEquals(
            "Hello, world!",
            $this->subCompiler->compile($this->template, $this->template->getContents())
        );
    }

    /**
     * Tests compiling a tag whose value is another tag
     */
    public function testCompilingTagWhoseValueIsAnotherTag()
    {
        error_log(5);
        // Order here is important
        // We're testing setting the inner-most tag first, and then the outer tag
        $this->template->setContents("{{!content!}}");
        $this->template->setTag("message", "world");
        $this->template->setTag("content", "Hello, {{!message!}}!");
        $this->assertEquals(
            "Hello, world!",
            $this->subCompiler->compile($this->template, $this->template->getContents())
        );
    }

    /**
     * Tests compiling a template with PHP code
     */
    public function testCompilingTemplateWithPHPCode()
    {
        error_log(6);
        $contents = $this->fileSystem->read(__DIR__ . "/.." . self::TEMPLATE_PATH_WITH_PHP_CODE);
        $this->template->setContents($contents);
        $user1 = new User(1, "foo");
        $user2 = new User(2, "bar");
        $this->template->setTag("listDescription", "usernames");
        $this->template->setVar("users", [$user1, $user2]);
        $this->template->setVar("coolestGuy", "Dave");
        $functionResult = $this->registerFunction();
        $this->assertEquals(
            'TEST List of usernames on ' . $functionResult . ':
<ul>
    <li>foo</li><li>bar</li></ul> 2 items
<br>Dave is a pretty cool guy. Alternative syntax works! I agree. Fake closing PHP tag: ?>',
            $this->subCompiler->compile($this->template, $this->template->getContents()));
    }

    /**
     * Tests compiling a variable inside tags
     */
    public function testCompilingVariableInsideTags()
    {
        error_log(7);
        $delimiters = [
            [
                Template::DEFAULT_OPEN_ESCAPED_TAG_DELIMITER,
                Template::DEFAULT_CLOSE_ESCAPED_TAG_DELIMITER
            ],
            [
                Template::DEFAULT_OPEN_UNESCAPED_TAG_DELIMITER,
                Template::DEFAULT_CLOSE_UNESCAPED_TAG_DELIMITER
            ]
        ];
        $templateContents = '<?php foreach(["a&w"] as $v): ?>%s$v%s<?php endforeach; ?>';
        $this->template->setContents(sprintf($templateContents, $delimiters[0][0], $delimiters[0][1]));
        $this->assertTrue(
            $this->stringsWithEncodedCharactersEqual(
                "a&amp;w",
                $this->subCompiler->compile($this->template, $this->template->getContents())
            )
        );
        $this->template->setContents(sprintf($templateContents, $delimiters[1][0], $delimiters[1][1]));
        $this->assertEquals("a&w", $this->subCompiler->compile($this->template, $this->template->getContents()));
    }

    /**
     * Tests compiling an escaped string
     */
    public function testEscapedString()
    {
        error_log(8);
        $this->template->setContents('{{"a&w"}}');
        $this->assertTrue(
            $this->stringsWithEncodedCharactersEqual(
                "a&amp;w",
                $this->subCompiler->compile($this->template, $this->template->getContents())
            )
        );
        $this->template->setContents("{{'a&w'}}");
        $this->assertTrue(
            $this->stringsWithEncodedCharactersEqual(
                "a&amp;w",
                $this->subCompiler->compile($this->template, $this->template->getContents())
            )
        );
    }

    /**
     * Tests an escaped tag with quotes
     */
    public function testEscapedTagWithQuotes()
    {
        error_log(9);
        $this->template->setContents('\{{" "}}"');
        $this->assertEquals('{{" "}}"', $this->subCompiler->compile($this->template, $this->template->getContents()));
    }

    /**
     * Tests compiling a template with a function that spans multiple lines
     */
    public function testFunctionThatSpansMultipleLines()
    {
        error_log(10);
        $this->compiler->registerTemplateFunction("foo", function ($input)
        {
            return $input . "bar";
        });
        $this->template->setContents("{{
        foo(
        'foo'
        )
        }}");
        $this->assertEquals("foobar", $this->subCompiler->compile($this->template, $this->template->getContents()));
    }

    /**
     * Tests compiling a template with a function that has spaces between the open and close tags
     */
    public function testFunctionWithSpacesBetweenTags()
    {
        error_log(11);
        $this->template->setContents('{{! foo("bar") !}}');
        $this->compiler->registerTemplateFunction("foo", function ($input)
        {
            echo $input;
        });
        $this->assertEquals("bar", $this->subCompiler->compile($this->template, $this->template->getContents()));
    }

    /**
     * Tests compiling a non-existent function
     */
    public function testInvalidFunction()
    {
        error_log(12);
        $this->setExpectedException(ViewCompilerException::class);
        $this->template->setContents('{{ foo() }}');
        $this->subCompiler->compile($this->template, $this->template->getContents());
    }

    /**
     * Tests compiling a template with multiple calls to the same function
     */
    public function testMultipleCallsOfSameFunction()
    {
        error_log(13);
        $this->compiler->registerTemplateFunction("foo",
            function ($param1 = null, $param2 = null)
            {
                if($param1 == null && $param2 == null)
                {
                    return "No params";
                }
                elseif($param1 == null)
                {
                    return "Param 2 set";
                }
                elseif($param2 == null)
                {
                    return "Param 1 set";
                }
                else
                {
                    return "Both params set";
                }
            }
        );
        $this->template->setContents(
            '{{!foo()!}}, {{!foo()!}}, {{!foo("bar")!}}, {{!foo(null, "bar")!}}, {{!foo("bar", "blah")!}}'
        );
        $this->assertEquals(
            'No params, No params, Param 1 set, Param 2 set, Both params set',
            $this->subCompiler->compile($this->template, $this->template->getContents())
        );
    }

    /**
     * Tests compiling nested PHP functions
     */
    public function testNestedPHPFunctions()
    {
        error_log(14);
        $this->template->setContents('{{!date(strtoupper("y"))!}}');
        $this->assertEquals(date("Y"), $this->subCompiler->compile($this->template, $this->template->getContents()));
    }

    /**
     * Tests compiling nested template functions
     */
    public function testNestedTemplateFunctions()
    {
        error_log(15);
        $this->compiler->registerTemplateFunction("foo", function()
        {
            return "bar";
        });
        $this->compiler->registerTemplateFunction("baz", function($input)
        {
            return strrev($input);
        });
        $this->template->setContents('{{!baz(foo())!}}');
        $this->assertEquals("rab", $this->subCompiler->compile($this->template, $this->template->getContents()));
    }

    /**
     * Tests that only outer quotes are stripped from string literals
     */
    public function testOnlyOuterQuotesGetStrippedFromStringLiterals()
    {
        error_log(16);
        $this->template->setVar("foo", true);
        $this->template->setContents('{{!$foo ? \' class="bar"\' : \'\'!}}');
        $this->assertEquals(' class="bar"', $this->subCompiler->compile($this->template, $this->template->getContents()));
        $this->template->setContents("{{!\$foo ? \" class='bar'\" : \"\"!}}");
        $this->assertEquals(" class='bar'", $this->subCompiler->compile($this->template, $this->template->getContents()));
    }

    /**
     * Tests compiling a PHP function
     */
    public function testPHPFunction()
    {
        error_log(17);
        $this->template->setContents('{{ date("Y") }}');
        $this->assertEquals(date("Y"), $this->subCompiler->compile($this->template, $this->template->getContents()));
    }

    /**
     * Tests compiling a PHP function with template function
     */
    public function testPHPFunctionWithTemplateFunction()
    {
        error_log(18);
        $this->compiler->registerTemplateFunction("foo", function()
        {
            return "Y";
        });
        $this->template->setContents('{{ date(foo()) }}');
        $this->assertEquals(date("Y"), $this->subCompiler->compile($this->template, $this->template->getContents()));
    }

    /**
     * Tests compiling a string literal with escaped quotes
     */
    public function testStringLiteralWithEscapedQuotes()
    {
        error_log(19);
        // Test escaped strings
        $this->template->setContents("{{'fo\'o'}}");
        $this->assertTrue($this->stringsWithEncodedCharactersEqual(
            "fo&#039;o",
            $this->subCompiler->compile($this->template, $this->template->getContents())
        ));
        $this->template->setContents('{{"fo\"o"}}');
        $this->assertTrue($this->stringsWithEncodedCharactersEqual(
            'fo&quot;o',
            $this->subCompiler->compile($this->template, $this->template->getContents())
        ));
        // Test unescaped strings
        $this->template->setContents("{{!'fo\'o'!}}");
        $this->assertEquals("fo'o", $this->subCompiler->compile($this->template, $this->template->getContents()));
        $this->template->setContents('{{!"fo\"o"!}}');
        $this->assertEquals('fo"o', $this->subCompiler->compile($this->template, $this->template->getContents()));
    }

    /**
     * Tests compiling a template with a tag that spans multiple lines
     */
    public function testTagThatSpansMultipleLines()
    {
        error_log(20);
        $this->template->setContents("{{
        foo
        }}");
        $this->template->setTag("foo", "bar");
        $this->assertEquals("bar", $this->subCompiler->compile($this->template, $this->template->getContents()));
    }

    /**
     * Tests tag whose value is PHP code
     */
    public function testTagWithPHPValue()
    {
        error_log(21);
        $this->template->setTag("foo", '$bar->blah();');
        $this->template->setContents('{{!foo!}}');
        $this->assertEquals('$bar->blah();', $this->subCompiler->compile($this->template, $this->template->getContents()));
    }

    /**
     * Tests compiling template function
     */
    public function testTemplateFunction()
    {
        error_log(22);
        $this->compiler->registerTemplateFunction("foo", function()
        {
            return "a&w";
        });
        $this->template->setContents('{{foo()}}');
        $this->assertTrue(
            $this->stringsWithEncodedCharactersEqual(
                "a&amp;w",
                $this->subCompiler->compile($this->template, $this->template->getContents())
            )
        );
        $this->template->setContents('{{!foo()!}}');
        $this->assertEquals("a&w", $this->subCompiler->compile($this->template, $this->template->getContents()));
    }

    /**
     * Tests compiling template function with string input
     */
    public function testTemplateFunctionWithStringInput()
    {
        error_log(23);
        $this->compiler->registerTemplateFunction("foo", function($input)
        {
            return strrev($input);
        });
        $this->template->setContents('{{!foo("bar")!}}');
        $this->assertEquals("rab", $this->subCompiler->compile($this->template, $this->template->getContents()));
    }

    /**
     * Tests compiling a template that uses custom delimiters
     */
    public function testTemplateWithCustomDelimiters()
    {
        error_log(24);
        $contents = $this->fileSystem->read(__DIR__ . "/.." . self::TEMPLATE_PATH_WITH_CUSTOM_TAG_DELIMITERS);
        $this->template->setContents($contents);
        $this->template->setDelimiters(Template::DELIMITER_TYPE_UNESCAPED_TAG, ["^^", "$$"]);
        $this->template->setDelimiters(Template::DELIMITER_TYPE_ESCAPED_TAG, ["++", "--"]);
        $this->template->setDelimiters(Template::DELIMITER_TYPE_STATEMENT, ["(*", "*)"]);
        $this->template->setTag("foo", "Hello");
        $this->template->setTag("bar", "world");
        $this->template->setTag("imSafe", "a&b");
        $this->template->setVar("today", time());
        $this->compiler->registerTemplateFunction("customDate", function($date, $format, $args)
        {
            return "foo";
        });
        $this->assertTrue(
            $this->stringsWithEncodedCharactersEqual(
                'Hello, world! (*show("parttest")*). ^^blah$$. a&amp;b. me too. c&amp;d. e&f. ++"g&h"--. ++ "i&j" --. ++blah--. Today escaped is foo and unescaped is foo. (*part("parttest")*)It worked(*endpart*).',
                $this->subCompiler->compile($this->template, $this->template->getContents())
            )
        );
    }

    /**
     * Tests compiling a template that uses the default delimiters
     */
    public function testTemplateWithDefaultDelimiters()
    {
        error_log(25);
        $contents = $this->fileSystem->read(__DIR__ . "/.." . self::TEMPLATE_PATH_WITH_DEFAULT_TAG_DELIMITERS);
        $this->template->setContents($contents);
        $this->template->setTag("foo", "Hello");
        $this->template->setTag("bar", "world");
        $this->template->setTag("imSafe", "a&b");
        $this->template->setVar("today", time());
        $this->compiler->registerTemplateFunction("customDate", function($date, $format, $args)
        {
            return "foo";
        });
        $this->assertTrue(
            $this->stringsWithEncodedCharactersEqual(
                'Hello, world! {%show("parttest")%}. {{!blah!}}. a&amp;b. me too. c&amp;d. e&f. {{"g&h"}}. {{ "i&j" }}. {{blah}}. Today escaped is foo and unescaped is foo. {%part("parttest")%}It worked{%endpart%}.',
                $this->subCompiler->compile($this->template, $this->template->getContents())
            )
        );
    }

    /**
     * Tests the ternary operator
     */
    public function testTernaryOperator()
    {
        error_log(26);
        $this->template->setVar("foo", true);
        $this->template->setContents('{{$foo ? "a&w" : ""}}');
        $this->assertTrue(
            $this->stringsWithEncodedCharactersEqual(
                "a&amp;w",
                $this->subCompiler->compile($this->template, $this->template->getContents())
            )
        );
        $this->template->setContents('{{!$foo ? "a&w" : ""!}}');
        $this->assertEquals("a&w", $this->subCompiler->compile($this->template, $this->template->getContents()));
    }

    /**
     * Tests compiling an unescaped string
     */
    public function testUnescapedString()
    {
        error_log(27);
        $this->template->setContents('{{!"foo"!}}');
        $this->assertEquals("foo", $this->subCompiler->compile($this->template, $this->template->getContents()));
        $this->template->setContents("{{!'foo'!}}");
        $this->assertEquals("foo", $this->subCompiler->compile($this->template, $this->template->getContents()));
    }
}