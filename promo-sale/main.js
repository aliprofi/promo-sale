/**
 * Новая функция для перехода по внешним ссылкам
 * Открывает ссылку в новой вкладке
 */
function GoTo(url) {
    window.open(url, '_blank', 'noopener,noreferrer');
}

document.addEventListener('DOMContentLoaded', function() {
    // Выбираем все теги <code> на странице
    const codeBlocks = document.querySelectorAll('code');

    if (codeBlocks.length > 0) {
        codeBlocks.forEach(function(block) {
            // Добавляем обработчик клика на каждый тег
            block.addEventListener('click', function() {
                const textToCopy = block.innerText;
                const originalText = block.innerText;

                // Используем современный API для работы с буфером обмена
                navigator.clipboard.writeText(textToCopy).then(function() {
                    // Успешно скопировано
                    block.innerText = 'Скопировано!';
                    block.classList.add('copied');

                    // Возвращаем исходный текст через 1.5 секунды
                    setTimeout(function() {
                        block.innerText = originalText;
                        block.classList.remove('copied');
                    }, 1500);

                }).catch(function(err) {
                    // Ошибка при копировании
                    console.error('Не удалось скопировать текст: ', err);
                    block.innerText = 'Ошибка копирования';
                     setTimeout(function() {
                        block.innerText = originalText;
                    }, 1500);
                });
            });
        });
    }
});

document.addEventListener('DOMContentLoaded', function() {
    // Выбираем все теги <code> на странице
    const codeBlocks = document.querySelectorAll('code');

    if (codeBlocks.length > 0) {
        codeBlocks.forEach(function(block) {
            // Добавляем обработчик клика на каждый тег
            block.addEventListener('click', function() {
                const textToCopy = block.innerText;
                const originalText = block.innerText;

                // Используем современный API для работы с буфером обмена
                navigator.clipboard.writeText(textToCopy).then(function() {
                    // Успешно скопировано
                    block.innerText = 'Скопировано!';
                    block.classList.add('copied');

                    // Возвращаем исходный текст через 1.5 секунды
                    setTimeout(function() {
                        block.innerText = originalText;
                        block.classList.remove('copied');
                    }, 1500);

                }).catch(function(err) {
                    // Ошибка при копировании
                    console.error('Не удалось скопировать текст: ', err);
                    block.innerText = 'Ошибка копирования';
                     setTimeout(function() {
                        block.innerText = originalText;
                    }, 1500);
                });
            });
        });
    }
});