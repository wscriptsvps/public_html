<?php
// Busca os dados dinâmicos para a página inicial
$online_users_count = count(User::getOnlineUsers());
$active_rooms_count = Room::countAll();
$popular_rooms = Room::getPopularRooms(); // CORRIGIDO: Usa a função correta do modelo Room
$subscriber_rooms = Room::getRoomsBySubscribers();
$general_rooms = Room::getGeneralRooms();
?>

<div class="text-center">
    <h1 class="text-4xl font-bold text-purple-800 mb-4">Bem-vindo ao <?php echo SITE_NAME; ?>!</h1>
    <p class="text-lg text-gray-600 mb-8">A sua nova plataforma para fazer amigos, partilhar ideias e conversar em tempo real.</p>
    
    <!-- Contadores Dinâmicos -->
    <div class="flex justify-center gap-8 mb-8">
        <div class="text-center">
            <p class="text-4xl font-bold text-green-500"><?php echo $online_users_count; ?></p>
            <p class="text-gray-500">Utilizadores Online</p>
        </div>
        <div class="text-center">
            <p class="text-4xl font-bold text-blue-500"><?php echo $active_rooms_count; ?></p>
            <p class="text-gray-500">Salas Disponíveis</p>
        </div>
    </div>

    <div class="flex justify-center gap-4">
        <a href="/register" class="bg-orange-500 text-white font-bold py-3 px-6 rounded-lg hover:bg-orange-600">Crie sua conta agora!</a>
        <a href="/rooms" class="bg-purple-600 text-white font-bold py-3 px-6 rounded-lg hover:bg-purple-700">Ver Todas as Salas</a>
    </div>
</div>

<!-- Secção de Salas de Assinantes em Destaque -->
<?php if (!empty($subscriber_rooms)): ?>
<div class="mt-16">
    <h2 class="text-3xl font-bold text-center text-gray-800 mb-8">Salas de Assinantes em Destaque</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <?php foreach ($subscriber_rooms as $room): ?>
            <div class="bg-white rounded-lg shadow-md flex flex-col overflow-hidden border-2 border-yellow-400">
                <div class="p-6 flex-grow">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-lg font-bold text-gray-800 truncate" title="<?php echo htmlspecialchars($room['name']); ?>"><?php echo htmlspecialchars($room['name']); ?></h3>
                        <span class="text-sm font-bold text-green-500 flex items-center gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015.537 4.097A6.97 6.97 0 008 16a6.97 6.97 0 00-1.5-4.33A5 5 0 016 11z" /></svg>
                            <?php echo count(User::getOnlineUsersInRoom($room['id'])); ?>
                        </span>
                    </div>
                    <p class="text-gray-600 text-sm h-12 overflow-hidden">Criada por: <strong><?php echo htmlspecialchars($room['creator_name']); ?></strong></p>
                </div>
                <div class="bg-gray-50 p-4">
                    <a href="/chat/room/<?php echo $room['slug']; ?>" class="block w-full text-center bg-purple-600 text-white font-bold py-2 px-4 rounded-lg hover:bg-purple-700 transition duration-300">
                        Entrar
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Secção de Salas Gerais -->
<div class="mt-16">
    <h2 class="text-3xl font-bold text-center text-gray-800 mb-8">Salas de Chat Principais</h2>
    <?php if (empty($general_rooms)): ?>
        <p class="text-center text-gray-500">Não há salas principais disponíveis de momento.</p>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php foreach ($general_rooms as $room): ?>
                <div class="bg-white rounded-lg shadow-md flex flex-col overflow-hidden">
                    <div class="p-6 flex-grow">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-lg font-bold text-gray-800 truncate" title="<?php echo htmlspecialchars($room['name']); ?>"><?php echo htmlspecialchars($room['name']); ?></h3>
                            <span class="text-sm font-bold text-green-500 flex items-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015.537 4.097A6.97 6.97 0 008 16a6.97 6.97 0 00-1.5-4.33A5 5 0 016 11z" /></svg>
                                <?php echo count(User::getOnlineUsersInRoom($room['id'])); ?>
                            </span>
                        </div>
                        <p class="text-gray-600 text-sm h-12 overflow-hidden"><?php echo htmlspecialchars($room['description']); ?></p>
                    </div>
                    <div class="bg-gray-50 p-4">
                        <a href="/chat/room/<?php echo $room['slug']; ?>" class="block w-full text-center bg-purple-600 text-white font-bold py-2 px-4 rounded-lg hover:bg-purple-700 transition duration-300">
                            Entrar
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Secção VIP -->
<div class="mt-16 bg-purple-700 text-white p-10 rounded-lg shadow-xl">
    <h2 class="text-3xl font-bold text-center text-orange-400 mb-4">Eleve a sua Experiência: Torne-se VIP!</h2>
    <p class="text-center text-purple-200 max-w-2xl mx-auto mb-8">Os membros VIP são a espinha dorsal da nossa comunidade e desfrutam de benefícios exclusivos que transformam a sua experiência no chat.</p>
    <div class="grid md:grid-cols-3 gap-8 text-center">
        <div>
            <h3 class="text-xl font-bold text-white mb-2">Avatar Personalizado</h3>
            <p class="text-purple-200">Destaque-se na multidão! Apenas membros VIP podem fazer upload de um avatar exclusivo e personalizado.</p>
        </div>
        <div>
            <h3 class="text-xl font-bold text-white mb-2">Crie as Suas Próprias Salas</h3>
            <p class="text-purple-200">Tenha o seu próprio espaço! Membros VIP podem criar e gerir as suas próprias salas de chat com acesso restrito.</p>
        </div>
        <div>
            <h3 class="text-xl font-bold text-white mb-2">Experiência Sem Anúncios</h3>
            <p class="text-purple-200">Navegue e converse sem interrupções. Os membros VIP não veem anúncios na sala de chat, garantindo foco total nas conversas.</p>
        </div>
    </div>
    <div class="text-center mt-8">
        <a href="/plans" class="bg-orange-500 text-white font-bold py-3 px-8 rounded-lg hover:bg-orange-600 transition duration-300">Ver Planos VIP</a>
    </div>
</div>
