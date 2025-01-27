<?php

namespace App\Tests\Util;

use App\Util\Util;
use PHPUnit\Framework\TestCase;

class UtilTest extends TestCase {

	public function provideEncodeStringCases() {
		return [
			[ 'test_Δôü', '_test_Dou' ],
			[ 'foo', '_foo' ],
			[ 'æ,þ,η,ŋ', '_ae_th_e_n' ],
			[ '.-!:?$', '_._____' ],
			[ '🎉', '__' ],
			[ 'Fóø Båř', '_Foo_Bar' ],
			// Greek
			[ 'Ξεσκεπάζω την ψυχοφθόρα σας βδελυγμία', '_Xeskepazo_ten_psychophthora_sas_bdelygmia' ],
			// Cyrillic
			[ 'Любя съешь щипцы вздохнёт мэр кайф жгуч', '_Luba_s_es__sipcy_vzdohnet_mer_kajf_zguc' ],
			// Arabic
			[ 'ابجد هوَّز حُطّي كلَمُن سَعْفَص قُرِشَت ثَخَدٌ ضَظَغ', '_abjd_hwaz_huty_klamun_sa__fas_qurishat_thakhadu__dazagh' ],
			// Hebrew
			[ 'עטלפ אבק נס דרק מעזגן שעתפוףץ כי האמ', '__tlp__bq_ns_drq_m_zgn_s_tpwpz_ky_h_m' ],
			// Polish
			[ 'Zażółć gęślą jaźń', '_Zazolc_gesla_jazn' ],
			// Turkish
			[ 'Pijamalı hasta yağız şoföre çabucak güvendi', '_Pijamali_hasta_yagiz_sofore_cabucak_guvendi' ],
			// // Czech
			[ 'Příliš žluťoučký kůň úpěl ďábelské ódy', '_Prilis_zlutoucky_kun_upel_dabelske_ody' ],
			// // Danish
			[ 'Høj bly gom vandt fræk sexquiz på wc', '_Hoj_bly_gom_vandt_fraek_sexquiz_pa_wc' ],
			// // Esperanto
			[ 'Eble ĉiu kvazaŭ-deca fuŝĥoraĵo ĝojigos homtipon', '_Eble_ciu_kvazau_deca_fushorajo_gojigos_homtipon' ],
		];
	}

	/**
	 * encodeString prefixes encoded strings with an incrementing counter
	 * which we omit from the tests by using assertStringEndsWith().
	 *
	 * @covers ::encodeString
	 * @dataProvider provideEncodeStringCases
	 */
	public function testEncodeString( $input, $expected ) {
		$this->assertStringEndsWith( $expected, Util::encodeString( $input ) );
	}
}
