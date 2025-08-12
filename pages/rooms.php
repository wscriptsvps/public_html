<?php
// 1. Busca todas as categorias e salas ativas
$categories = RoomCategory::getAll();
$all_rooms = Room::getAll();

// 2. Organiza as salas dentro de cada categoria
$rooms_by_category = [];
foreach ($all_rooms as $room) {
    if ($room['status'] === 'active') {
        $category_id = $room['category_id'] ?? 'general';
        $rooms_by_category[$category_id][] = $room;
    }
}

// 3. Ordena as salas dentro de cada categoria (mais antigas primeiro)
foreach ($rooms_by_category as $key => &$rooms_in_cat) {
    usort($rooms_in_cat, function($a, $b) {
        return strtotime($a['created_at']) <=> strtotime($b['created_at']);
    });
}

// Exibe mensagens de erro de acesso vindas do controlador do chat
$access_error = $_SESSION['access_error'] ?? null;
unset($_SESSION['access_error']);
?>

<div class="max-w-7xl mx-auto">
    <h1 class="text-3xl font-bold text-center text-purple-800 mb-4">Salas de Bate-Papo</h1>
    <p class="text-center text-gray-600 mb-8">Escolha uma categoria e entre numa sala para começar a conversar.</p>

    <?php if ($access_error): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
            <?php echo $access_error; ?>
        </div>
    <?php endif; ?>

    <!-- BARRA DE PESQUISA -->
    <div class="mb-8 max-w-lg mx-auto">
        <input type="search" id="room-search-input" placeholder="Pesquisar por nome ou tema..." class="w-full px-4 py-3 border rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
    </div>

    <!-- Loop através das categorias -->
    <div id="categories-container" class="space-y-12">
        <?php foreach ($categories as $category): ?>
            <?php if (!empty($rooms_by_category[$category['id']])): ?>
            <div class="category-section">
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-10 h-10 text-purple-600"><?php echo $category['icon_svg']; ?></div>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($category['name']); ?></h2>
                        <p class="text-gray-500"><?php echo htmlspecialchars($category['description']); ?></p>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <?php foreach ($rooms_by_category[$category['id']] as $room): ?>
                        <!-- Card da Sala -->
                        <div class="bg-white rounded-lg shadow-md flex flex-col overflow-hidden room-card">
                            <div class="p-6 flex-grow">
                                <div class="flex items-center justify-between mb-2">
                                    <h3 class="text-lg font-bold text-gray-800 room-name truncate"><?php echo htmlspecialchars($room['name']); ?></h3>
                                    <span class="px-2 py-1 font-semibold rounded-full text-xs <?php echo $room['type'] === 'vip' ? 'bg-yellow-200 text-yellow-800' : 'bg-blue-200 text-blue-800'; ?>"><?php echo ucfirst($room['type']); ?></span>
                                </div>
                                <p class="text-gray-600 text-sm h-12 overflow-hidden"><?php echo htmlspecialchars($room['description']); ?></p>
                            </div>
                            <div class="bg-gray-50 p-4">
                                <?php
                                // --- LÓGICA DE PERMISSÃO CORRIGIDA ---
                                $user_account_type = $_SESSION['user_account_type'] ?? 'guest';
                                
                                // Define o nível de acesso requerido pela sala
                                $required_level = $room['type'] === 'public' ? 'common' : $room['access_level'];
                                
                                // Verifica se o utilizador tem permissão
                                $can_enter = userHasAccess($user_account_type, $required_level);
                                ?>
                                <?php if ($can_enter): ?>
                                    <a href="/chat/room/<?php echo $room['slug']; ?>" class="block w-full text-center bg-orange-500 text-white font-bold py-2 px-4 rounded-lg hover:bg-orange-600">Entrar</a>
                                <?php else: ?>
                                    <a href="/plans" class="block w-full text-center bg-purple-600 text-white font-bold py-2 px-4 rounded-lg hover:bg-purple-700" title="Requer um plano superior">Requer <?php echo ucfirst($required_level); ?></a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    <div id="no-results-message" class="hidden text-center text-gray-500 mt-8">
        <p>Nenhuma sala encontrada com o termo pesquisado.</p>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('room-search-input');
    const categoriesContainer = document.getElementById('categories-container');
    const noResultsMessage = document.getElementById('no-results-message');

    searchInput.addEventListener('input', (e) => {
        const searchTerm = e.target.value.toLowerCase().trim();
        let totalVisibleCount = 0;

        categoriesContainer.querySelectorAll('.category-section').forEach(section => {
            let sectionVisibleCount = 0;
            section.querySelectorAll('.room-card').forEach(card => {
                const roomName = card.querySelector('.room-name').textContent.toLowerCase();
                
                if (roomName.includes(searchTerm)) {
                    card.style.display = 'flex';
                    sectionVisibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            // Esconde a categoria inteira se nenhuma sala corresponder
            if (sectionVisibleCount === 0) {
                section.style.display = 'none';
            } else {
                section.style.display = 'block';
                totalVisibleCount++;
            }
        });

        if (totalVisibleCount === 0) {
            noResultsMessage.classList.remove('hidden');
        } else {
            noResultsMessage.classList.add('hidden');
        }
    });
});
</script>
