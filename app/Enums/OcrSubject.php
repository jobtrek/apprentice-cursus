<?php

namespace App\Enums;

enum OcrSubject: string
{
    case Anglais = 'subjects/Anglais';
    case Maths = 'subjects/Maths';
    case Allemand = 'subjects/Allemand';
    case Francais = 'subjects/Français';
    case Histoire = 'subjects/Histoire';
    case EcoDroit = 'subjects/Eco&Droit';
    case Sciences = 'subjects/Sciences';
    case Ecg = 'subjects/ECG';
    case Tib = 'subjects/TIB';
    case Other = 'OTHER';

    public function description(): string
    {
        return match ($this) {
            self::Anglais => "Anglais: English language test (English texts, grammar, vocabulary, writing). Headers or grading grids can be in French. A test titled 'English Test' is Anglais even when the topic is IT (websites, networks, emails)",
            self::Maths => 'Mathématiques (CBE Math, mathématiques fondamentales, mathématiques renforcées): equations, calculations, geometry, functions, exercises with numbers',
            self::Allemand => 'Allemand: German language test (German words or texts, Wortschatztest, Deutsch). Vocabulary tests list French words to translate into German. German headers like Name + Vorname, Klasse, Datum, Grammatik, Hören, Insgesamt mean Allemand, even when the class code is XMPT or TASV',
            self::Francais => 'Français: French language itself: grammar, spelling, conjugation, prefixes and suffixes, literature. Not every test written in French is a French test',
            self::Histoire => 'Histoire et institutions politiques: historical events (revolutions, world wars, colonisation), analysis of a historical source, letter, map or image. Written in French',
            self::EcoDroit => 'Économie et droit (ECDR), maturity classes like XMPT: law (droit des obligations, droit pénal, droit commercial), economics',
            self::Sciences => 'Sciences naturelles (chimie, physique)',
            self::Ecg => 'Culture générale (ECG, CG), apprentice classes like DEVA: society, apprenticeship contract, bank and post, media and press, analysis of a poster or advert, oral argumentation, basic economics. Written in French',
            self::Tib => 'Travail interdisciplinaire dans les branches',
            self::Other => 'None of the subjects or modules above',
        };
    }

    /** Name shown to the apprentice: « Anglais », « Autre ». */
    public function label(): string
    {
        return $this === self::Other ? 'Autre' : str_replace('subjects/', '', $this->value);
    }

    /**
     * Name of the grade tree leaf this subject is graded on, null when the tree has no such leaf.
     * ponytail: only the subjects whose leaf carries their name; add a case when the tree has others.
     */
    public function branchName(): ?string
    {
        return match ($this) {
            self::Anglais => 'Anglais',
            self::Maths => 'Mathématiques',
            default => null,
        };
    }

    /**
     * Options for a laravel/ai Choice question: value => description.
     *
     * @return array<string, string>
     */
    public static function choices(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(fn (self $subject) => $subject->description(), self::cases()),
        );
    }
}
