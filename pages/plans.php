<?php
// Busca todos os planos ativos para exibir na página
$plans = Plan::getAll();
?>

<div class="text-center max-w-3xl mx-auto">
    <h1 class="text-4xl font-bold text-purple-800 mb-4">Escolha o Plano VIP Perfeito para Si</h1>
    <p class="text-lg text-gray-600 mb-8">Desbloqueie funcionalidades exclusivas, apoie a comunidade e eleve a sua experiência no chat para o próximo nível. Cada plano oferece mais benefícios!</p>
</div>

<div class="mt-10 grid md:grid-cols-2 lg:grid-cols-3 gap-8">
    <?php foreach ($plans as $plan): ?>
        <?php if ($plan['status'] === 'active'): ?>
        <div class="bg-white p-8 rounded-lg shadow-lg text-center flex flex-col border-t-4 border-purple-500">
            <h2 class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($plan['name']); ?></h2>
            <p class="text-purple-600 font-semibold mt-2"><?php echo $plan['interval_months']; ?> Mês(es)</p>
            <p class="text-5xl font-bold text-gray-900 my-6">
                R$ <span class="align-top"><?php echo number_format($plan['price'], 2, ',', '.'); ?></span>
            </p>
            
            <ul class="text-gray-600 space-y-3 mb-8 flex-grow">
                <li class="flex items-center justify-center gap-2">
                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    <span>Avatar Personalizado</span>
                </li>
                <li class="flex items-center justify-center gap-2">
                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    <span>Acesso a Salas VIP</span>
                </li>
                <li class="flex items-center justify-center gap-2">
                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    <span>Sem Anúncios</span>
                </li>
                <li class="flex items-center justify-center gap-2">
                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    <span>Ícone de Destaque</span>
                </li>
                <li class="flex items-center justify-center gap-2 font-bold text-purple-700">
                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    <span>Crie <?php echo $plan['room_creation_limit']; ?> Sala(s)</span>
                </li>
            </ul>

            <?php if (Auth::isLoggedIn()): ?>
                <form action="/stripe/create-checkout-session.php" method="POST">
                    <input type="hidden" name="plan_id" value="<?php echo $plan['id']; ?>">
                    <button type="submit" class="w-full bg-orange-500 text-white font-bold py-3 px-6 rounded-lg hover:bg-orange-600 transition duration-300">
                        Assinar Agora
                    </button>
                </form>
            <?php else: ?>
                <a href="/login?redirect=plans" class="w-full block bg-gray-500 text-white font-bold py-3 px-6 rounded-lg hover:bg-gray-600 transition duration-300">
                    Faça Login para Assinar
                </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    <?php endforeach; ?>
</div>
