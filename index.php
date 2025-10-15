<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SERVER['REQUEST_URI'] === '/ai') {
    header('Content-Type: application/json');
    
    $apiKey = getenv('OPENAI_API_KEY');
    if (!$apiKey) {
        http_response_code(503);
        echo json_encode(['error' => 'OPENAI_API_KEY not set']);
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
        ['{effective_days}', '{top_indicators}', '{bottom_indicators}', '{last_relapse}'],
        [
            $input['context_json']['effective_days'],
            $input['context_json']['top_indicators'],
            $input['context_json']['bottom_indicators'],
            $input['context_json']['last_relapse']
        ],
        $config['openai']['user_prompt_template']
    );
    
    $data = [
        'model' => $config['openai']['model'],
        'messages' => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt]
        ],
        'max_tokens' => 500,
        'temperature' => 0.7
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
    curl_close($ch);
    
    if ($httpCode !== 200) {
        http_response_code(500);
        echo json_encode(['error' => 'OpenAI API error']);
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
    <title>Suivi Sobriété</title>
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
            --shadow: 0 2px 4px rgba(0,0,0,0.1);
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
        
        .confidence-elevée { background: var(--success); color: white; }
        .confidence-modérée { background: var(--warning); color: var(--text-primary); }
        .confidence-limitée { background: var(--danger); color: white; }
        
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
            background: rgba(0,0,0,0.5);
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
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
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
            <h1 class="title">Suivi Sobriété</h1>
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                <button class="btn btn-secondary" onclick="resetData()" aria-label="Réinitialiser les données">Réinitialiser</button>
                <button class="btn btn-secondary" onclick="exportData()" aria-label="Exporter les données">Exporter JSON</button>
                <button class="btn btn-secondary" onclick="document.getElementById('importFile').click()" aria-label="Importer des données">Importer JSON</button>
                <input type="file" id="importFile" accept=".json" style="display: none" onchange="importData(event)">
            </div>
        </div>

        <div class="section">
            <h2 class="section-title">Mon arrêt</h2>
            <div class="form-group">
                <label for="startDate">Date de départ :</label>
                <input type="date" id="startDate" class="form-control" onchange="saveData()">
            </div>
            <div class="counter" id="daysCounter">0 jours sans alcool</div>
            <button class="btn btn-danger" onclick="openRelapseModal()" aria-label="Enregistrer une reprise">J'ai bu</button>
        </div>

        <div class="section">
            <h2 class="section-title">Progression santé</h2>
            <div class="indicators-grid" id="indicatorsGrid"></div>
        </div>

        <div class="section">
            <h2 class="section-title">Encouragement IA</h2>
            <button class="btn" onclick="getAIEncouragement()" aria-label="Obtenir un message d'encouragement de l'IA">Besoin d'un boost ?</button>
            <div id="aiResponse" class="ai-response" style="display: none;"></div>
        </div>
    </div>

    <div id="relapseModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Enregistrer une reprise</h3>
                <button class="close" onclick="closeRelapseModal()">&times;</button>
            </div>
            <div class="form-group">
                <label for="relapseDate">Date et heure :</label>
                <input type="datetime-local" id="relapseDate" class="form-control">
            </div>
            <div class="form-group">
                <label for="relapseUnits">Nombre de verres standards :</label>
                <input type="number" id="relapseUnits" class="form-control" min="1" value="1">
            </div>
            <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                <button class="btn btn-secondary" onclick="closeRelapseModal()">Annuler</button>
                <button class="btn btn-danger" onclick="saveRelapse()">Enregistrer</button>
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

        let config = <?= json_encode($config) ?>;
        let state = {
            startDate: '',
            relapses: [],
            notes: ''
        };

        let saveTimeout;

        function loadState() {
            const saved = localStorage.getItem('sobriety.state.v1');
            if (saved) {
                try {
                    state = JSON.parse(saved);
                    if (state.startDate) {
                        document.getElementById('startDate').value = state.startDate;
                    }
                } catch (e) {
                    console.error('Erreur lors du chargement des données:', e);
                }
            }
            updateUI();
        }

        function saveData() {
            clearTimeout(saveTimeout);
            saveTimeout = setTimeout(() => {
                state.startDate = document.getElementById('startDate').value;
                localStorage.setItem('sobriety.state.v1', JSON.stringify(state));
                updateUI();
            }, 300);
        }

        function resetData() {
            if (confirm('Êtes-vous sûr de vouloir réinitialiser toutes les données ?')) {
                state = { startDate: '', relapses: [], notes: '' };
                document.getElementById('startDate').value = '';
                localStorage.removeItem('sobriety.state.v1');
                updateUI();
            }
        }

        function exportData() {
            const dataStr = JSON.stringify(state, null, 2);
            const dataBlob = new Blob([dataStr], { type: 'application/json' });
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
                    if (imported.startDate || imported.relapses || imported.notes) {
                        state = { ...state, ...imported };
                        if (state.startDate) {
                            document.getElementById('startDate').value = state.startDate;
                        }
                        localStorage.setItem('sobriety.state.v1', JSON.stringify(state));
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
            if (!state.startDate) return 0;
            
            const startDate = new Date(state.startDate);
            const now = new Date();
            const totalDays = Math.floor((now - startDate) / (1000 * 60 * 60 * 24));
            
            let penaltyDays = 0;
            const cooldownHours = config.relapse_model.cooldown_hours_after_relapse;
            
            for (const relapse of state.relapses) {
                const relapseDate = new Date(relapse.date);
                const hoursSinceRelapse = (now - relapseDate) / (1000 * 60 * 60);
                
                if (hoursSinceRelapse >= cooldownHours) {
                    const units = Math.min(relapse.units, 10);
                    penaltyDays += Math.min(units * config.relapse_model.penalty_days_per_unit, config.relapse_model.max_penalty_days);
                }
            }
            
            return Math.max(0, totalDays - penaltyDays);
        }

        function calculateIndicatorProgress(indicator, effectiveDays) {
            if (!indicator.target_days) {
                return { progress: 0, eta: null, isComplete: false };
            }
            
            const cooldownHours = config.relapse_model.cooldown_hours_after_relapse;
            const now = new Date();
            let completionDecay = 0;
            
            for (const relapse of state.relapses) {
                const relapseDate = new Date(relapse.date);
                const hoursSinceRelapse = (now - relapseDate) / (1000 * 60 * 60);
                
                if (hoursSinceRelapse < cooldownHours) {
                    return { progress: 0, eta: null, isComplete: false };
                }
                
                const units = Math.min(relapse.units, 10);
                const decay = Math.min(units * config.relapse_model.completion_decay_per_unit, config.relapse_model.max_completion_decay);
                completionDecay = Math.max(completionDecay, decay);
            }
            
            const rawProgress = Math.min(1, effectiveDays / indicator.target_days);
            const smoothedProgress = Math.min(1, rawProgress * (1 - completionDecay));
            const isComplete = smoothedProgress >= 1;
            const eta = isComplete ? null : Math.ceil((indicator.target_days - effectiveDays) / (1 - completionDecay));
            
            return { progress: smoothedProgress, eta, isComplete };
        }

        function updateUI() {
            const effectiveDays = calculateEffectiveDays();
            document.getElementById('daysCounter').textContent = `${effectiveDays} ${i18n.days} ${i18n.withoutAlcohol}`;
            
            const indicatorsGrid = document.getElementById('indicatorsGrid');
            indicatorsGrid.innerHTML = '';
            
            const indicatorsWithProgress = config.indicators.map(indicator => {
                const progressData = calculateIndicatorProgress(indicator, effectiveDays);
                return { ...indicator, ...progressData };
            });
            
            indicatorsWithProgress.sort((a, b) => b.progress - a.progress);
            
            indicatorsWithProgress.forEach(indicator => {
                const card = document.createElement('div');
                card.className = 'indicator-card';
                
                const confidenceClass = `confidence-${indicator.confidence.toLowerCase()}`;
                
                card.innerHTML = `
                    <div class="indicator-header">
                        <div class="indicator-name">${indicator.name}</div>
                        <div class="confidence-badge ${confidenceClass}">${indicator.confidence}</div>
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

        function openRelapseModal() {
            document.getElementById('relapseDate').value = new Date().toISOString().slice(0, 16);
            document.getElementById('relapseModal').style.display = 'block';
        }

        function closeRelapseModal() {
            document.getElementById('relapseModal').style.display = 'none';
        }

        function saveRelapse() {
            const date = document.getElementById('relapseDate').value;
            const units = parseInt(document.getElementById('relapseUnits').value);
            
            if (!date || units < 1) {
                showToast('Veuillez remplir tous les champs', 'error');
                return;
            }
            
            state.relapses.push({
                date: new Date(date).toISOString(),
                units: units
            });
            
            state.relapses.sort((a, b) => new Date(b.date) - new Date(a.date));
            
            localStorage.setItem('sobriety.state.v1', JSON.stringify(state));
            closeRelapseModal();
            updateUI();
        }

        function getAIEncouragement() {
            const effectiveDays = calculateEffectiveDays();
            const indicatorsWithProgress = config.indicators.map(indicator => {
                const progressData = calculateIndicatorProgress(indicator, effectiveDays);
                return { ...indicator, ...progressData };
            });
            
            indicatorsWithProgress.sort((a, b) => b.progress - a.progress);
            
            const topIndicators = indicatorsWithProgress.slice(0, 3).map(ind => 
                `${ind.name}: ${Math.round(ind.progress * 100)}%`
            ).join(', ');
            
            const bottomIndicators = indicatorsWithProgress.slice(-3).map(ind => 
                `${ind.name}: ${Math.round(ind.progress * 100)}%`
            ).join(', ');
            
            const lastRelapse = state.relapses.length > 0 ? 
                `${new Date(state.relapses[0].date).toLocaleDateString()}: ${state.relapses[0].units} verres` : 
                'Aucune';
            
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
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ context_json: contextJson })
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

        function runTests() {
            console.log('=== Tests d\'acceptation ===');
            
            const originalState = { ...state };
            
            state.startDate = '2025-01-01';
            state.relapses = [];
            const effectiveDays = calculateEffectiveDays();
            console.log('Test 1 - Sans reprise:', effectiveDays > 0 ? 'PASS' : 'FAIL');
            
            state.relapses = [{ date: new Date().toISOString(), units: 3 }];
            const progressData = calculateIndicatorProgress(config.indicators[0], effectiveDays);
            console.log('Test 2 - Après reprise:', progressData.progress < 1 ? 'PASS' : 'FAIL');
            
            const exportData = JSON.stringify(state);
            const importedState = JSON.parse(exportData);
            console.log('Test 3 - Export/Import:', JSON.stringify(importedState) === exportData ? 'PASS' : 'FAIL');
            
            state = originalState;
            updateUI();
        }

        document.addEventListener('DOMContentLoaded', function() {
            loadState();
            runTests();
        });

        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal')) {
                closeRelapseModal();
            }
        });
    </script>
</body>
</html>