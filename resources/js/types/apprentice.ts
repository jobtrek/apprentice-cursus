/** Apprenti·e tel·le qu'envoyé·e par `ApprenticeResource`. */
export interface Apprentice {
    id: number;
    name: string;
    /** `null` quand la filière n'est pas reconnue. */
    track: 'IT' | 'EC' | null;
    /** Calculée depuis les notes ; `null` tant que le calcul n'existe pas. */
    year: '1ère' | '2ème' | '3ème' | '4ème' | null;
    coach: string | null;
    /** Faux pour un coach qui ne suit pas (encore) cet·te apprenti·e. */
    canView: boolean;
}
