<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anuncie Conosco - Alugue seu Imóvel via WhatsApp</title>

    {{-- Tailwind via Vite --}}
    @vite('resources/css/app.css')

    {{-- Font Awesome & Plus Jakarta Sans --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .gradient-text {
            background: linear-gradient(90deg, #2563eb, #06b6d4);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .bg-pattern {
            background-image: radial-gradient(#e5e7eb 1px, transparent 1px);
            background-size: 20px 20px;
        }
    </style>
</head>

<body class="bg-slate-50 text-slate-900">

    <!-- Header Simples -->
    <nav class="sticky top-0 z-50 bg-white/80 backdrop-blur-md border-b border-slate-200">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            <div class="flex items-center gap-2">
                <div class="bg-white-600 p-2 rounded-lg">
                </div>

            </div>
            <div class="flex items-center gap-3">
                <a href="/locador/login"
                    class="text-slate-600 px-4 py-2.5 rounded-full font-bold hover:text-slate-900 transition">
                    Entrar
                </a>
                <a href="#anunciar"
                    class="bg-slate-900 text-white px-6 py-2.5 rounded-full font-bold hover:bg-slate-800 transition">
                    Anunciar Agora
                </a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="relative pt-16 pb-24 overflow-hidden bg-pattern">
        <div class="container mx-auto px-6 grid lg:grid-cols-2 gap-16 items-center">
            <div class="space-y-8">
                <h1 class="text-5xl lg:text-6xl font-extrabold leading-tight">
                    Alugue seu imóvel 10x mais rápido pelo nosso WhatsApp.
                </h1>
                <p class="text-lg text-slate-600 leading-relaxed max-w-xl">
                    Chega de portais lentos e centenas de mensagens de curiosos. Nós conectamos seu imóvel diretamente
                    aos locatários através da maior vitrine automatizada via WhatsApp da região.
                </p>
                <div class="flex flex-col sm:flex-row gap-4">
                    <a href="#anunciar"
                        class="bg-green-600 text-white px-8 py-4 rounded-2xl font-bold text-lg shadow-lg shadow-green-200 hover:bg-green-700 transition text-center flex items-center justify-center gap-2">
                        <i class="fab fa-whatsapp"></i> Quero Anunciar Meu Imóvel
                    </a>
                </div>
                <div class="flex items-center gap-6 pt-4">
                    <div class="flex -space-x-3">
                        <img src="https://i.pravatar.cc/100?u=1" class="w-10 h-10 rounded-full border-2 border-white"
                            alt="Proprietário">
                        <img src="https://i.pravatar.cc/100?u=2" class="w-10 h-10 rounded-full border-2 border-white"
                            alt="Proprietário">
                        <img src="https://i.pravatar.cc/100?u=3" class="w-10 h-10 rounded-full border-2 border-white"
                            alt="Proprietário">
                    </div>
                    <p class="text-sm text-slate-500 font-medium">+150 proprietários alugaram este mês</p>
                </div>
            </div>

            <div class="relative">
                <div class="bg-white rounded-[2.5rem] shadow-2xl p-4 border border-slate-100">
                    <div
                        class="rounded-[2rem] overflow-hidden border border-slate-200 shadow-lg transition-transform hover:scale-[1.02] duration-300">
                        <img src="https://plus.unsplash.com/premium_photo-1663040286675-8dc3d0d563e5?q=80&w=1170&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D"
                            alt="Interface do nosso WhatsApp Imobiliário" class="w-full h-auto block" />
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Por que o Nosso WhatsApp? -->
    <section class="py-24 bg-white">
        <div class="container mx-auto px-6">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-bold text-slate-900 mb-4">Por que anunciar no nosso WhatsApp?</h2>
                <p class="text-slate-500 max-w-2xl mx-auto">Eliminamos as barreiras entre o seu imóvel e o inquilino
                    perfeito através de tecnologia de ponta.</p>
            </div>

            <div class="grid md:grid-cols-3 gap-12">
                <div class="text-center space-y-4">
                    <div
                        class="w-16 h-16 bg-blue-100 text-blue-600 rounded-2xl flex items-center justify-center text-2xl mx-auto">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <h4 class="text-xl font-bold">Resposta Instantânea</h4>
                    <p class="text-slate-600">Nosso robô atende 24h por dia. Enquanto você dorme, seu imóvel está sendo
                        apresentado com fotos e detalhes.</p>
                </div>
                <div class="text-center space-y-4">
                    <div
                        class="w-16 h-16 bg-green-100 text-green-600 rounded-2xl flex items-center justify-center text-2xl mx-auto">
                        <i class="fas fa-filter"></i>
                    </div>
                    <h4 class="text-xl font-bold">Filtro de Curiosos</h4>
                    <p class="text-slate-600">O sistema qualifica o inquilino antes de chegar até você. Você só recebe
                        contatos de quem realmente tem o perfil e crédito.</p>
                </div>
                <div class="text-center space-y-4">
                    <div
                        class="w-16 h-16 bg-purple-100 text-purple-600 rounded-2xl flex items-center justify-center text-2xl mx-auto">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h4 class="text-xl font-bold">Relatório de Alcance</h4>
                    <p class="text-slate-600">Saiba exatamente quantas pessoas viram seu imóvel, quantas pediram
                        detalhes e quantas solicitaram visitas.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Cadastro Simples -->
    <section id="anunciar" class="py-24 bg-slate-900 text-white">
        <div class="container mx-auto px-6 max-w-4xl">
            <div class="bg-white text-slate-900 rounded-[3rem] p-8 lg:p-16 shadow-2xl">
                <div class="text-center mb-12">
                    <h2 class="text-3xl font-bold mb-4">Cadastre seu Imóvel Hoje</h2>
                    <p class="text-slate-500">Preencha os dados básicos e nossa equipe ativará o anúncio em nosso robô.
                    </p>
                </div>

                <form class="grid md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="text-sm font-bold text-slate-700">Seu Nome Completo</label>
                        <input type="text" placeholder="Ex: João Silva"
                            class="w-full p-4 bg-slate-100 border-none rounded-xl focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>
                    <div class="space-y-2">
                        <label class="text-sm font-bold text-slate-700">Seu WhatsApp de Contato</label>
                        <input type="tel" placeholder="(00) 00000-0000"
                            class="w-full p-4 bg-slate-100 border-none rounded-xl focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>
                    <div class="md:col-span-2 space-y-2">
                        <label class="text-sm font-bold text-slate-700">Título do Imóvel (Como quer anunciar?)</label>
                        <input type="text" placeholder="Ex: Apartamento 2 qtos mobiliado no Centro"
                            class="w-full p-4 bg-slate-100 border-none rounded-xl focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>
                    <div class="space-y-2">
                        <label class="text-sm font-bold text-slate-700">Valor do Aluguel</label>
                        <input type="number" placeholder="R$ 0,00"
                            class="w-full p-4 bg-slate-100 border-none rounded-xl focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>
                    <div class="space-y-2">
                        <label class="text-sm font-bold text-slate-700">Localização (Bairro/Cidade)</label>
                        <input type="text" placeholder="Ex: Batel, Curitiba"
                            class="w-full p-4 bg-slate-100 border-none rounded-xl focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>
                    <div
                        class="md:col-span-2 p-8 border-2 border-dashed border-slate-200 rounded-3xl text-center space-y-4">
                        <div class="text-4xl text-slate-300">
                            <i class="fas fa-cloud-upload-alt"></i>
                        </div>
                        <p class="text-slate-500 font-medium">Arraste as fotos aqui ou <span
                                class="text-blue-600 font-bold cursor-pointer">selecione arquivos</span></p>
                        <p class="text-xs text-slate-400">Mínimo de 3 fotos de alta qualidade.</p>
                    </div>
                    <div class="md:col-span-2 pt-4">
                        <button type="submit"
                            class="w-full bg-slate-900 text-white py-5 rounded-2xl font-bold text-xl hover:bg-slate-800 transition shadow-xl">
                            Enviar para o WhatsApp Imobiliário
                        </button>
                    </div>
                </form>
                <p class="text-center mt-6 text-xs text-slate-400 uppercase font-bold tracking-widest">
                    Ao enviar, você concorda com nossos termos de privacidade.
                </p>
            </div>
        </div>
    </section>

    <!-- Depoimento de Proprietário -->
    <section class="py-24 bg-white">
        <div class="container mx-auto px-6 text-center">
            <div class="max-w-3xl mx-auto space-y-8">
                <div class="text-yellow-400 text-2xl">
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                </div>
                <blockquote class="text-2xl font-medium text-slate-800 italic">
                    "Eu estava há 3 meses tentando alugar meu apartamento nos portais tradicionais. Depois que anunciei
                    no WhatsApp do Apartamento WhatsApp, o robô filtrou os candidatos e eu aluguei em menos de 1
                    semana."
                </blockquote>
                <div>
                    <p class="font-bold text-slate-900">Ricardo Menezes</p>
                    <p class="text-slate-500">Proprietário de 3 imóveis em São Paulo</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-white py-12 border-t border-slate-100">
        <div class="container mx-auto px-6 flex flex-col md:flex-row justify-between items-center gap-8">
            <div class="flex items-center gap-2">
                <div class="bg-green-600 p-1.5 rounded-lg text-white">
                    <i class="fab fa-whatsapp"></i>
                </div>
                <span class="font-bold text-slate-800">Apartamento WhatsApp <span
                        class="text-green-600">Locadores</span></span>
            </div>
            <p class="text-slate-400 text-sm">© 2026 Apartamento WhatsApp. A maior rede de automação imobiliária.</p>
            <div class="flex gap-6">
                <a href="#" class="text-slate-400 hover:text-slate-900"><i class="fab fa-instagram text-xl"></i></a>
                <a href="#" class="text-slate-400 hover:text-slate-900"><i class="fab fa-whatsapp text-xl"></i></a>
            </div>
        </div>
    </footer>

</body>

</html>