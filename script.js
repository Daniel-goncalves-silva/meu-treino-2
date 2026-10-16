const grupos = [
    "peito",
    "costas",
    "pernas",
    "ombros",
    "bracos",
    "triceps",
    "abdomen"
];

const nomes = {
    peito: "Peito",
    costas: "Costas",
    pernas: "Pernas",
    ombros: "Ombros",
    bracos: "Bíceps e antebraço",
    triceps: "Tríceps",
    abdomen: "Abdômen"
};

const tempoDescanso = 48 * 60 * 60 * 1000;

function atualizarGrupo(grupo) {

    const status = document.getElementById("status-" + grupo);
    const data = document.getElementById("data-" + grupo);
    const botao = document.querySelector(
        '[data-grupo="' + grupo + '"]'
    );

    const treinoSalvo = localStorage.getItem("treino-" + grupo);

    if (treinoSalvo === null) {
        status.textContent = "Pode treinar";
        data.textContent = "";
        botao.textContent = "Marcar como treinado";
        return;
    }

    const dataTreino = new Date(treinoSalvo);
    const agora = new Date();

    const fimDescanso = new Date(
        dataTreino.getTime() + tempoDescanso
    );

    if (agora < fimDescanso) {

        status.textContent = "Em descanso";

        data.textContent =
            "Disponível novamente em: " +
            fimDescanso.toLocaleString("pt-BR");

        botao.textContent = "Desfazer marcação";

    } else {

        status.textContent = "Pode treinar novamente";

        data.textContent =
            "Último treino: " +
            dataTreino.toLocaleString("pt-BR");

        botao.textContent = "Marcar como treinado";
    }
}

function inicioDaSemana() {

    const hoje = new Date();

    const dia = hoje.getDay();

    const diferenca = dia === 0 ? 6 : dia - 1;

    const inicio = new Date(hoje);

    inicio.setDate(hoje.getDate() - diferenca);
    inicio.setHours(0, 0, 0, 0);

    return inicio;
}

function atualizarSemana() {

    const lista = document.getElementById("lista-semana");

    lista.innerHTML = "";

    const inicio = inicioDaSemana();

    grupos.forEach(function (grupo) {

        const item = document.createElement("li");

        const treinoSalvo = localStorage.getItem("treino-" + grupo);

        if (treinoSalvo !== null) {

            const dataTreino = new Date(treinoSalvo);

            if (dataTreino >= inicio) {
                item.textContent = nomes[grupo] + " - Treinado ✓";
            } else {
                item.textContent = nomes[grupo] + " - Não treinado";
            }

        } else {
            item.textContent = nomes[grupo] + " - Não treinado";
        }

        lista.appendChild(item);
    });
}

const botoes = document.querySelectorAll(".botao-treino");

botoes.forEach(function (botao) {

    botao.addEventListener("click", function () {

        const grupo = botao.dataset.grupo;

        const treinoSalvo = localStorage.getItem("treino-" + grupo);

        if (treinoSalvo !== null) {

            const dataTreino = new Date(treinoSalvo);
            const agora = new Date();

            if (agora - dataTreino < tempoDescanso) {

                localStorage.removeItem("treino-" + grupo);

                atualizarGrupo(grupo);
                atualizarSemana();

                return;
            }
        }

        const agora = new Date();

        localStorage.setItem(
            "treino-" + grupo,
            agora.toISOString()
        );

        atualizarGrupo(grupo);
        atualizarSemana();
    });
});

grupos.forEach(function (grupo) {
    atualizarGrupo(grupo);
});

atualizarSemana();
