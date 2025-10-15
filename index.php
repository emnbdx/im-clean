<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SERVER['REQUEST_URI'] === '/ai') {
    header('Content-Type: application/json');

    $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? null;
    if (!$apiKey) {
        http_response_code(503);
        echo json_encode(['error' => 'OPENAI_API_KEY not set. Please configure your API key.']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || !isset($input['context_json'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid request']);
        exit;
    }

    $config = json_decode(file_get_contents(__DIR__ . '/config.sobriete.json'), true);

    $systemPrompt = $config['openai']['system_prompt'];
    $userPrompt = str_replace(
        '{{context_json}}',
        json_encode($input['context_json'], JSON_UNESCAPED_UNICODE),
        $config['openai']['user_prompt_template']
    );

    $data = [
        'model' => $config['openai']['model'],
        'messages' => [
            ['role' => 'developer', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt]
        ]
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        http_response_code(500);
        echo json_encode(['error' => 'CURL Error: ' . $curlError]);
        exit;
    }

    if ($httpCode !== 200) {
        http_response_code(500);
        echo json_encode(['error' => 'OpenAI API error (HTTP ' . $httpCode . '): ' . $response]);
        exit;
    }

    $result = json_decode($response, true);
    echo json_encode(['message' => $result['choices'][0]['message']['content']]);
    exit;
}

$config = json_decode(file_get_contents(__DIR__ . '/config.sobriete.json'), true);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stop Alcool</title>
    <style>
        :root {
            --bg-primary: #ffffff;
            --bg-secondary: #f8f9fa;
            --text-primary: #212529;
            --text-secondary: #6c757d;
            --accent: #007bff;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
            --border: #dee2e6;
            --shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            --radius: 8px;
            --spacing: 1rem;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --bg-primary: #1a1a1a;
                --bg-secondary: #2d2d2d;
                --text-primary: #ffffff;
                --text-secondary: #adb5bd;
                --border: #495057;
            }
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: var(--bg-primary);
            color: var(--text-primary);
            line-height: 1.6;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: var(--spacing);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: var(--spacing);
            margin-bottom: 2rem;
            padding: var(--spacing);
            background: var(--bg-secondary);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }

        .title {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--accent);
        }

        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: var(--radius);
            background: var(--accent);
            color: white;
            cursor: pointer;
            font-size: 0.9rem;
            transition: background-color 0.2s;
        }

        .btn:hover {
            background: #0056b3;
        }

        .btn-secondary {
            background: var(--text-secondary);
        }

        .btn-danger {
            background: var(--danger);
        }

        .section {
            margin-bottom: 2rem;
            padding: var(--spacing);
            background: var(--bg-secondary);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }

        .section-title {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: var(--spacing);
            color: var(--accent);
        }

        .form-group {
            margin-bottom: var(--spacing);
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            background: var(--bg-primary);
            color: var(--text-primary);
        }

        .counter {
            font-size: 2rem;
            font-weight: bold;
            color: var(--success);
            text-align: center;
            margin: var(--spacing) 0;
        }

        .indicators-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: var(--spacing);
        }

        .indicator-card {
            background: var(--bg-primary);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: var(--spacing);
            transition: transform 0.2s;
        }

        .indicator-card:hover {
            transform: translateY(-2px);
        }

        .indicator-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .indicator-name {
            font-weight: bold;
            font-size: 1rem;
        }

        .confidence-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: bold;
        }

        .evidence-élevée {
            background: var(--success);
            color: white;
        }

        .evidence-modérée {
            background: var(--warning);
            color: var(--text-primary);
        }

        .evidence-limitée {
            background: var(--danger);
            color: white;
        }

        .indicator-category {
            color: var(--text-secondary);
            font-size: 0.85rem;
            margin-bottom: 0.5rem;
        }

        .indicator-description {
            font-size: 0.9rem;
            margin-bottom: var(--spacing);
        }

        .progress-container {
            margin-bottom: 0.5rem;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background: var(--border);
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--accent), var(--success));
            transition: width 0.3s ease;
        }

        .progress-text {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            margin-top: 0.25rem;
        }

        .eta {
            color: var(--text-secondary);
            font-style: italic;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: var(--bg-primary);
            padding: 2rem;
            border-radius: var(--radius);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            width: 90%;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--spacing);
        }

        .close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text-secondary);
        }

        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--danger);
            color: white;
            padding: 1rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            z-index: 1001;
            display: none;
        }

        .ai-response {
            background: var(--bg-primary);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: var(--spacing);
            margin-top: var(--spacing);
            white-space: pre-wrap;
            line-height: 1.6;
        }

        .loading {
            text-align: center;
            padding: var(--spacing);
            color: var(--text-secondary);
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                align-items: stretch;
            }

            .indicators-grid {
                grid-template-columns: 1fr;
            }

            .modal-content {
                width: 95%;
                padding: 1rem;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1 class="title">Stop Alcool</h1>
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                <button class="btn btn-secondary" onclick="openConfigModal()" aria-label="Configuration">⚙️ Config</button>
                <button class="btn btn-secondary" onclick="resetData()" aria-label="Réinitialiser les données">Réinitialiser</button>
                <button class="btn btn-secondary" onclick="exportData()" aria-label="Exporter les données">Exporter JSON</button>
                <button class="btn btn-secondary" onclick="document.getElementById('importFile').click()" aria-label="Importer des données">Importer JSON</button>
                <input type="file" id="importFile" accept=".json" style="display: none" onchange="importData(event)">
            </div>
        </div>

        <div class="section">
            <h2 class="section-title">Mon arrêt</h2>
            <div class="counter" id="daysCounter">0 jours sans alcool</div>
            <button class="btn btn-danger" onclick="openRelapseModal()" aria-label="Enregistrer une reprise">J'ai bu</button>
        </div>

        <div class="section">
            <h2 class="section-title">💬 Encouragement IA</h2>
            <button class="btn" onclick="getAIEncouragement()" aria-label="Obtenir un message d'encouragement de l'IA">Besoin d'un boost ?</button>
            <div id="aiResponse" class="ai-response" style="display: none;"></div>
        </div>

        <div class="section">
            <h2 class="section-title">📊 Progression santé</h2>
            <div class="indicators-grid" id="indicatorsGrid"></div>
        </div>
    </div>

    <div id="configModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Configuration</h3>
                <button class="close" onclick="closeConfigModal()">&times;</button>
            </div>
            <div class="form-group">
                <label for="configApiKey">Clé API OpenAI :</label>
                <input type="password" id="configApiKey" class="form-control" placeholder="sk-...">
                <small style="color: var(--text-secondary); font-size: 0.8rem;">Votre clé API sera stockée localement</small>
            </div>
            <div class="form-group">
                <label for="configStartDate">Date de début d'arrêt :</label>
                <input type="date" id="configStartDate" class="form-control">
            </div>
            <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                <button class="btn btn-secondary" onclick="closeConfigModal()">Annuler</button>
                <button class="btn" onclick="saveConfig()">Sauvegarder</button>
            </div>
        </div>
    </div>

    <div id="relapseModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Reprise d'alcool</h3>
                <button class="close" onclick="closeRelapseModal()">&times;</button>
            </div>
            <div style="text-align: center; padding: 1rem;">
                <p>Êtes-vous sûr de vouloir enregistrer une reprise ?</p>
                <p style="color: var(--text-secondary); font-size: 0.9rem;">Cela remettra votre compteur à 0.</p>
                <p style="color: var(--text-secondary); font-size: 0.8rem; margin-top: 0.5rem;">
                    Date : <span id="relapseDateDisplay"></span>
                </p>
            </div>
            <div style="display: flex; gap: 0.5rem; justify-content: center;">
                <button class="btn btn-secondary" onclick="closeRelapseModal()">Annuler</button>
                <button class="btn btn-danger" onclick="saveRelapse()">Confirmer</button>
            </div>
        </div>
    </div>

    <div id="toast" class="toast"></div>

    <script>
        const i18n = {
            days: 'jours',
            withoutAlcohol: 'sans alcool',
            longTerm: 'Long cours / surveillance',
            daysRemaining: 'jours restants',
            loading: 'Chargement...',
            error: 'Erreur',
            success: 'Succès'
        };

        let appConfig = <?= json_encode($config) ?>;

        let userConfig = {
            apiKey: '',
            startDate: ''
        };


        function loadState() {
            const savedConfig = localStorage.getItem('sobriety.config.v1');
            if (savedConfig) {
                try {
                    userConfig = JSON.parse(savedConfig);
                } catch (e) {
                    console.error('Erreur lors du chargement de la configuration:', e);
                }
            }

            // Mettre à jour la date d'affichage de la modal de rechute
            updateRelapseDateDisplay();

            updateUI();
        }

        function updateRelapseDateDisplay() {
            const now = new Date();
            const dateStr = now.toLocaleDateString('fr-FR', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            const dateElement = document.getElementById('relapseDateDisplay');
            if (dateElement) {
                dateElement.textContent = dateStr;
            }
        }


        function resetData() {
            if (confirm('Êtes-vous sûr de vouloir réinitialiser toutes les données ?')) {
                userConfig = {
                    apiKey: '',
                    startDate: ''
                };
                localStorage.removeItem('sobriety.config.v1');
                updateUI();
            }
        }

        function exportData() {
            const dataStr = JSON.stringify(userConfig, null, 2);
            const dataBlob = new Blob([dataStr], {
                type: 'application/json'
            });
            const url = URL.createObjectURL(dataBlob);
            const link = document.createElement('a');
            link.href = url;
            link.download = 'sobriety-data.json';
            link.click();
            URL.revokeObjectURL(url);
        }

        function importData(event) {
            const file = event.target.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = function(e) {
                try {
                    const imported = JSON.parse(e.target.result);
                    if (imported.startDate || imported.notes) {
                        userConfig = {
                            ...userConfig,
                            ...imported
                        };
                        // Date de début gérée via la configuration
                        localStorage.setItem('sobriety.config.v1', JSON.stringify(userConfig));
                        updateUI();
                        showToast('Données importées avec succès', 'success');
                    } else {
                        showToast('Format de fichier invalide', 'error');
                    }
                } catch (e) {
                    showToast('Erreur lors de l\'importation', 'error');
                }
            };
            reader.readAsText(file);
            event.target.value = '';
        }

        function calculateEffectiveDays() {
            if (!userConfig.startDate) return 0;

            const startDate = new Date(userConfig.startDate);
            const now = new Date();
            const totalDays = Math.floor((now - startDate) / (1000 * 60 * 60 * 24));

            return totalDays;
        }

        function calculateIndicatorProgress(indicator, effectiveDays) {
            if (!indicator.target_days) {
                return {
                    progress: 0,
                    eta: null,
                    isComplete: false
                };
            }

            // Si pas de jours effectifs (rechute), retourner 0
            if (effectiveDays <= 0) {
                return {
                    progress: 0,
                    eta: null,
                    isComplete: false
                };
            }

            const rawProgress = Math.min(1, effectiveDays / indicator.target_days);
            const isComplete = rawProgress >= 1;

            // Calculer l'ETA simplement
            let eta = null;
            if (!isComplete) {
                const remainingDays = indicator.target_days - effectiveDays;
                if (remainingDays > 0) {
                    eta = Math.ceil(remainingDays);
                }
            }

            return {
                progress: rawProgress,
                eta,
                isComplete
            };
        }

        function updateUI() {
            const effectiveDays = calculateEffectiveDays();
            document.getElementById('daysCounter').textContent = `${effectiveDays} ${i18n.days} ${i18n.withoutAlcohol}`;

            const indicatorsGrid = document.getElementById('indicatorsGrid');
            indicatorsGrid.innerHTML = '';

            const indicatorsWithProgress = appConfig.indicators.map(indicator => {
                const progressData = calculateIndicatorProgress(indicator, effectiveDays);
                return {
                    ...indicator,
                    ...progressData
                };
            });

            indicatorsWithProgress.sort((a, b) => b.progress - a.progress);

            indicatorsWithProgress.forEach(indicator => {
                const card = document.createElement('div');
                card.className = 'indicator-card';

                const evidenceClass = `evidence-${indicator.evidence_level.toLowerCase()}`;

                card.innerHTML = `
                    <div class="indicator-header">
                        <div class="indicator-name">${indicator.label}</div>
                        <div class="confidence-badge ${evidenceClass}">${indicator.evidence_level}</div>
                    </div>
                    <div class="indicator-category">${indicator.category}</div>
                    <div class="indicator-description">${indicator.description}</div>
                    ${indicator.target_days ? `
                        <div class="progress-container">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: ${indicator.progress * 100}%"></div>
                            </div>
                            <div class="progress-text">
                                <span>${Math.round(indicator.progress * 100)}%</span>
                                ${indicator.eta ? `<span class="eta">${indicator.eta} ${i18n.daysRemaining}</span>` : ''}
                            </div>
                        </div>
                    ` : `<div class="progress-text">${i18n.longTerm}</div>`}
                `;

                indicatorsGrid.appendChild(card);
            });
        }

        function openConfigModal() {
            document.getElementById('configApiKey').value = userConfig.apiKey;
            document.getElementById('configStartDate').value = userConfig.startDate;
            document.getElementById('configModal').style.display = 'block';
        }

        function closeConfigModal() {
            document.getElementById('configModal').style.display = 'none';
        }

        function saveConfig() {
            userConfig.apiKey = document.getElementById('configApiKey').value;
            userConfig.startDate = document.getElementById('configStartDate').value;

            localStorage.setItem('sobriety.config.v1', JSON.stringify(userConfig));

            closeConfigModal();
            updateUI();
            showToast('Configuration sauvegardée', 'success');
        }

        function openRelapseModal() {
            updateRelapseDateDisplay();
            document.getElementById('relapseModal').style.display = 'block';
        }

        function closeRelapseModal() {
            document.getElementById('relapseModal').style.display = 'none';
        }

        function saveRelapse() {
            // Remettre la startDate à la date actuelle (reset du compteur)
            userConfig.startDate = new Date().toISOString().split('T')[0]; // Format YYYY-MM-DD

            localStorage.setItem('sobriety.config.v1', JSON.stringify(userConfig));
            closeRelapseModal();
            updateUI();
            showToast('Compteur remis à zéro', 'success');
        }

        function getAIEncouragement() {
            if (!userConfig.apiKey) {
                showToast('Veuillez configurer votre clé API OpenAI', 'error');
                openConfigModal();
                return;
            }

            const effectiveDays = calculateEffectiveDays();
            const indicatorsWithProgress = appConfig.indicators.map(indicator => {
                const progressData = calculateIndicatorProgress(indicator, effectiveDays);
                return {
                    ...indicator,
                    ...progressData
                };
            });

            indicatorsWithProgress.sort((a, b) => b.progress - a.progress);

            const topIndicators = indicatorsWithProgress.slice(0, 3).map(ind =>
                `${ind.label}: ${Math.round(ind.progress * 100)}%`
            ).join(', ');

            const bottomIndicators = indicatorsWithProgress.slice(-3).map(ind =>
                `${ind.label}: ${Math.round(ind.progress * 100)}%`
            ).join(', ');

            const lastRelapse = 'Aucune';

            const contextJson = {
                effective_days: effectiveDays,
                top_indicators: topIndicators,
                bottom_indicators: bottomIndicators,
                last_relapse: lastRelapse
            };

            const aiResponse = document.getElementById('aiResponse');
            aiResponse.style.display = 'block';
            aiResponse.innerHTML = '<div class="loading">' + i18n.loading + '</div>';

            fetch('/ai', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-API-Key': userConfig.apiKey
                    },
                    body: JSON.stringify({
                        context_json: contextJson
                    })
                })
                .then(response => {
                    if (!response.ok) {
                        if (response.status === 503) {
                            throw new Error('OPENAI_API_KEY not set');
                        }
                        throw new Error('Erreur serveur');
                    }
                    return response.json();
                })
                .then(data => {
                    aiResponse.innerHTML = data.message;
                })
                .catch(error => {
                    aiResponse.innerHTML = 'Erreur: ' + error.message;
                    if (error.message.includes('OPENAI_API_KEY')) {
                        showToast('Clé API OpenAI manquante', 'error');
                    }
                });
        }

        function showToast(message, type = 'error') {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.style.display = 'block';
            toast.style.background = type === 'success' ? 'var(--success)' : 'var(--danger)';

            setTimeout(() => {
                toast.style.display = 'none';
            }, 3000);
        }

        document.addEventListener('DOMContentLoaded', function() {
            loadState();
        });

        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal')) {
                closeRelapseModal();
                closeConfigModal();
            }
        });
    </script>
</body>

</html>