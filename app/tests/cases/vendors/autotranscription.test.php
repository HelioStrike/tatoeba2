<?php
App::import('Vendor', 'Autotranscription');

class AutotranscriptionTestCase extends CakeTestCase {
    function startTest() {
        $this->AT =& new Autotranscription();
    }

    function endTest() {
        unset($this->AT);
    }

    function _assertCheck($method, $sentence, $transcription, $true) {
        $errors = array();
        $result = $this->AT->{$method}($sentence, $transcription, $errors);
        if ($true)
            $this->assertTrue($result, "$method “${sentence}” → “${transcription}” should pass check, error is ".implode("\n", $errors));
        else
            $this->assertFalse($result, "$method “${sentence}” → “${transcription}” should NOT pass check");
    }

    function testFuriganaSyntax() {
        $testGood = array(
            '行けそう。' => array(
                '[行|い]けそう。',
            ),
            /* Allow mixing with other scripts */
            'ＡとＢは違う。' => array(
                '[Ａ|えい]と[Ｂ|びー]は[違|ちが]う。'
            ),
            '「やっと２人になれたね。」' => array(
                '「やっと[２人|ふたり]になれたね。」',
            ),
            'Perfumeの曲' => array(
                '[Perfume|パフューム]の[曲|きょく]',
            ),
            /* Allow spaces */
            '今は？ 今は？' => array('[今|いま]は？ [今|いま]は？'),
            '今は？　今は？' => array('[今|いま]は？　[今|いま]は？'),
        );
        $testBad = array(
            '行けそう。' => array(
                /* No spaces */
                '[行|い]け そう 。',
                /* No furigana */
                '行けそう。',
                /* Invalid furigana */
                '[行|]けそう。',
                '[行|行]けそう。',
                '[行|a]けそう。',
                /* Syntax error */
                '[|い]けそう。',
                '[行|いけそう。',
                '[行|い]]けそう。',
                '[行|い|]けそう。',
                '[行い]けそう。',
                '[行|い]けそう[|]。',
                /* Transcription different from the sentence */
                '[行|い]けそ。',
                '[行|い]けそう',
                '[逝|い]けそう。',
            ),
            'Perfumeの曲' => array(
                /* Everything that is not kana should have furi */
                'Perfumeの[曲|きょく]',
                '[Perfume|]の[曲|きょく]',
            ),
        );
        $this->assertValidTranscriptions('jpn', 'Jpan', 'Hrkt', $testGood);
        $this->assertInvalidTranscriptions('jpn', 'Jpan', 'Hrkt', $testBad);
    }

    function assertTranscriptions($lang, $fromScript, $toScript, $transcriptions, $validity) {
        $method = "${lang}_${fromScript}_to_${toScript}_validate";
        foreach ($transcriptions as $from => $tos)
            foreach ($tos as $to)
                $this->_assertCheck($method, $from, $to, $validity);
    }

    function assertInvalidTranscriptions($lang, $fromScript, $toScript, $transcriptions) {
        $this->assertTranscriptions($lang, $fromScript, $toScript, $transcriptions, false);
    }

    function assertValidTranscriptions($lang, $fromScript, $toScript, $transcriptions) {
        $this->assertTranscriptions($lang, $fromScript, $toScript, $transcriptions, true);
    }

    function _assertFurigana($kanji, $reading, $expected) {
        $result = $this->AT->formatFurigana($kanji, $reading);
        $this->assertEqual($expected, $result, "furigana should be formatted like “${expected}”, got “${result}”");
    }

    function test_formatFurigana() {
        $this->_assertFurigana('男', 'おとこ', '[男|おとこ]');
        $this->_assertFurigana('男の子', 'おとこのこ', '[男|おとこ]の[子|こ]');
        $this->_assertFurigana('聞き覚え', 'ききおぼえ', '[聞|き]き[覚|おぼ]え');
        $this->_assertFurigana('生き字引', 'いきじびき', '[生|い]き[字引|じびき]');
        $this->_assertFurigana('青天の霹靂', 'せいてんのへきれき', '[青天|せいてん]の[霹靂|へきれき]');
        $this->_assertFurigana('物の具', 'もののぐ', '[物|もの]の[具|ぐ]');
        $this->_assertFurigana('合い印', 'あいいん', '[合|あ]い[印|いん]');
        $this->_assertFurigana('飼い犬', 'かいいぬ', '[飼|か]い[犬|いぬ]');
        $this->_assertFurigana('冷や奴', 'ひややっこ', '[冷|ひ]や[奴|やっこ]');
        $this->_assertFurigana('縫い糸', 'ぬいいと', '[縫|ぬ]い[糸|いと]');
        $this->_assertFurigana('差し潮', 'さししお', '[差|さ]し[潮|しお]');
        $this->_assertFurigana('食い意地', 'くいいじ', '[食|く]い[意地|いじ]');
        $this->_assertFurigana('四つ辻', 'よつつじ', '[四|よ]つ[辻|つじ]');

        /* Remove furigana on numbers since they are almost always wrong.
           Mecab parses them individually, e.g. 10 reads いちぜろ. */
        $this->_assertFurigana('１', 'いち', '１');
        $this->_assertFurigana('1', 'いち', '1');
    }

    function testPinyin() {
        $testGood = array(
            '你不得不制造一些借口。' => array(
                'Ni3 bu4de2bu4 zhi4zao4 yi1xie1 jie4kou3.',
            ),
        );
        $testBad = array(
            '你不得不制造一些借口。' => array(
                'Ni3 bu4de2bu4 製 zao4 yi1xie1 jie4kou3.',
            ),
        );
        $this->assertValidTranscriptions('cmn', 'Hant', 'Latn', $testGood);
        $this->assertInvalidTranscriptions('cmn', 'Hant', 'Latn', $testBad);
    }
}