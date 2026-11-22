<?php

namespace App\Http\Controllers;

use App\Models\DocumentVerification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Révocation d'un document émis.
 *
 * Un diplôme peut être délivré à tort (erreur de saisie, fraude). Le QR
 * code imprimé ne peut pas être effacé du papier — en revanche, la page
 * publique peut répondre « révoqué » au lieu de « authentique ».
 *
 * C'est donc un statut, pas une suppression : la ligne reste, datée et
 * motivée. Un employeur qui scanne un document révoqué l'apprend, et
 * l'établissement garde la trace de sa décision.
 *
 * Accès réservé au niveau 1 (Super admin) : révoquer un diplôme engage
 * l'établissement, ce n'est pas une opération de scolarité courante.
 */
class RevocationDocumentController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->input('q', ''));
        $statut = $request->input('statut', 'valide');

        $documents = DocumentVerification::query()
            ->when(in_array($statut, ['valide', 'revoque'], true),
                fn ($query) => $query->where('statut', $statut))
            ->when($q !== '', function ($query) use ($q) {
                $term = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';
                $query->where(function ($sub) use ($term) {
                    $sub->where('nom_complet', 'like', $term)
                        ->orWhere('matricule', 'like', $term)
                        ->orWhere('code', 'like', $term)
                        ->orWhere('reference', 'like', $term);
                });
            })
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        $compte = [
            'valide' => DocumentVerification::where('statut', 'valide')->count(),
            'revoque' => DocumentVerification::where('statut', 'revoque')->count(),
        ];

        return view('revocation-documents', compact('documents', 'q', 'statut', 'compte'));
    }

    /**
     * Révoque un document. Le motif est obligatoire : une révocation sans
     * justification n'a aucune valeur devant un étudiant ou un employeur.
     */
    public function revoquer(Request $request, int $id)
    {
        $donnees = $request->validate([
            'motif' => ['required', 'string', 'min:10', 'max:255'],
        ], [
            'motif.required' => 'Le motif de la révocation est obligatoire.',
            'motif.min' => 'Le motif doit être explicite (au moins 10 caractères).',
        ]);

        $document = DocumentVerification::findOrFail($id);

        if (! $document->estValide()) {
            return back()->with('erreur', 'Ce document est déjà révoqué.');
        }

        $document->update([
            'statut' => 'revoque',
            'motif_revocation' => $donnees['motif'],
            'revoque_le' => now(),
        ]);

        return back()->with(
            'succes',
            "Le document de {$document->nom_complet} est révoqué. "
            . 'Toute vérification par QR code affichera désormais cet état.'
        );
    }

    /**
     * Rétablit un document révoqué par erreur.
     *
     * Nécessaire : une révocation décidée sur une homonymie se corrige.
     * La trace de la révocation est effacée, mais la ligne d'audit du
     * modèle reste (le DocumentVerification n'est pas auditable, on
     * journalise donc manuellement ci-dessous).
     */
    public function retablir(Request $request, int $id)
    {
        $document = DocumentVerification::findOrFail($id);

        if ($document->estValide()) {
            return back()->with('erreur', 'Ce document est déjà valide.');
        }

        /* Trace dans `audits` : le rétablissement efface la révocation,
           il doit rester une preuve de l'aller-retour. On écrit la ligne
           directement — le modèle DocumentVerification n'est pas branché
           sur owen-it/laravel-auditing, et le paquet activitylog n'est
           pas installé (appeler activity() levait une erreur fatale). */
        DB::table('audits')->insert([
            'user_type' => get_class($request->user() ?? Auth::user()),
            'user_id' => optional($request->user() ?? Auth::user())->getKey(),
            'event' => 'updated',
            'auditable_type' => DocumentVerification::class,
            'auditable_id' => $document->getKey(),
            'old_values' => json_encode([
                'statut' => $document->statut,
                'motif_revocation' => $document->motif_revocation,
                'revoque_le' => optional($document->revoque_le)->toDateTimeString(),
            ], JSON_UNESCAPED_UNICODE),
            'new_values' => json_encode(['statut' => 'valide'], JSON_UNESCAPED_UNICODE),
            'url' => $request->fullUrl(),
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $document->update([
            'statut' => 'valide',
            'motif_revocation' => null,
            'revoque_le' => null,
        ]);

        return back()->with('succes', "Le document de {$document->nom_complet} est de nouveau valide.");
    }
}
