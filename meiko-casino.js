document.addEventListener('DOMContentLoaded', () => {
    let multiplier = 1.00;
    let crashed = false;
    let crashGameInterval;

    const startCrashButton = document.getElementById('start-crash');
    const cashoutButton = document.getElementById('cashout');
    const crashBetInput = document.getElementById('crash_bet');
    const crashForm = document.getElementById('crash-form');
    const multiplierDisplay = document.getElementById('multiplier');

    if (startCrashButton) {
        startCrashButton.addEventListener('click', function () {
            this.disabled = true;
            crashed = false;
            placeBet();
        });
    }

    if (cashoutButton) {
        cashoutButton.addEventListener('click', function () {
            if (!crashed) {
                clearInterval(crashGameInterval);
                this.disabled = true;
                if (startCrashButton) {
                    startCrashButton.disabled = false;
                }
                cashOut();
            }
            resetGame();
        });
    }

    function resetGame() {
        multiplier = 1.00;
        if (multiplierDisplay) {
            multiplierDisplay.innerText = '1.00x';
        }
        clearInterval(crashGameInterval);
    }

    function placeBet() {
        if (!crashBetInput || !crashForm) {
            console.error('Crash form or bet input element not found.');
            return;
        }

        let betAmount = crashBetInput.value;

        let xhr = new XMLHttpRequest();
        xhr.open('POST', meikoCasino.ajax_url, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

        xhr.onreadystatechange = function () {
            if (this.readyState === 4 && this.status === 200) {
                let response = JSON.parse(this.responseText);
                if (response.error) {
                    alert(response.error);
                    resetGame();
                    if (startCrashButton) {
                        startCrashButton.disabled = false;
                    }
                    if (cashoutButton) {
                        cashoutButton.disabled = true;
                    }
                } else {
                    crashForm.dataset.crashMultiplier = response.crash_multiplier;
                    startGame();
                }
            }
        };

        xhr.send(`action=place_crash_bet&bet_amount=${betAmount}&casino_security=${meikoCasino.security}`);
    }

    function startGame() {
        const crashMultiplier = parseFloat(crashForm.dataset.crashMultiplier);

        crashGameInterval = setInterval(() => {
            if (crashed) {
                clearInterval(crashGameInterval);
                return;
            }

            multiplier += 0.01;
            if (multiplierDisplay) {
                multiplierDisplay.innerText = multiplier.toFixed(2) + 'x';
            }

            if (multiplier >= crashMultiplier) {
                crashed = true;
                displayCrashPopup(crashMultiplier);
                clearInterval(crashGameInterval);
            }
        }, 100);

        if (cashoutButton) {
            cashoutButton.disabled = false;
        }
    }

    function displayCrashPopup(crashMultiplier) {
        const popup = document.createElement('div');
        popup.className = 'popup';
        popup.innerHTML = `
            <div class="popup-content">
                <p>The game has crashed at ${crashMultiplier.toFixed(2)}x!</p>
                <button id="close-popup">OK</button>
            </div>
        `;
        document.body.appendChild(popup);

        const closePopupButton = document.getElementById('close-popup');
        if (closePopupButton) {
            closePopupButton.addEventListener('click', function () {
                document.body.removeChild(popup);
                window.location.reload();
            });
        }

        if (cashoutButton) {
            cashoutButton.disabled = true;
        }
        if (startCrashButton) {
            startCrashButton.disabled = false;
        }
    }
    // Cash out logic
    function cashOut() {
        let xhr = new XMLHttpRequest();
        xhr.open('POST', meikoCasino.ajax_url, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

        xhr.onreadystatechange = function () {
            if (this.readyState === 4 && this.status === 200) {
                let response = JSON.parse(this.responseText);
                if (response.error) {
                    alert(response.error);
                } else {
                    alert(`You cashed out at ${multiplier.toFixed(2)}x! New balance: $${response.new_balance}`);
                }
            } else if (this.readyState === 4) {
                alert('An error occurred while cashing out.');
            }
        };

        xhr.send(`action=cash_out_crash&multiplier=${multiplier.toFixed(2)}&casino_security=${meikoCasino.security}`);
    }

    function spinRoulette(resultNumber) {
        const wheel = document.getElementById('roulette-wheel');
        const numberWidth = document.querySelector('.roulette-number').offsetWidth;
        const totalNumbers = 49; // Number of unique positions in one cycle
        const translateXPosition = (100 / totalNumbers) * resultNumber + '%';

        wheel.style.transition = 'transform 3s ease-in-out';
        wheel.style.transform = `translateX(-${translateXPosition})`;

        setTimeout(() => {
            wheel.style.transition = 'none';
            wheel.style.transform = `translateX(0px)`;

            // Highlight the result number
            const sections = wheel.getElementsByClassName('roulette-number');
            for (const section of sections) {
                section.classList.remove('highlighted');
            }
            document.querySelector(`.roulette-number[data-number="${resultNumber}"]`).classList.add('highlighted');
        }, 3000);
    }

    function playRouletteAnimation() {
        const betAmount = parseFloat(document.getElementById('roulette_bet').value);
        const chosenColor = document.getElementById('color').value;

        // Validate inputs
        if (isNaN(betAmount) || betAmount <= 0 || !chosenColor) {
            alert('Invalid input. Please check your bet amount and chosen color.');
            return;
        }

        // Make AJAX request to get result from the server
        const data = {
            action: 'play_roulette_animation',
            bet_amount: betAmount,
            chosen_color: chosenColor,
            casino_security: meikoCasino.casino_nonce
        };

        jQuery.post(meikoCasino.ajax_url, data, function(response) {
            console.log('Server Response:', response); // Debugging line

            if (response.success) {
                const resultNumber = response.result_number;
                const resultColor = response.result_color;

                // Constants
                const totalNumbers = 49; // Number of unique positions
                const wheel = document.getElementById('roulette-wheel');
                const numberWidth = document.querySelector('.roulette-number').offsetWidth;

                // Debugging information
                console.log('Result Number:', resultNumber);

                // Try different offsets until you find the correct one
                const offset = 6; // Adjust this if necessary
                const adjustedResultIndex = (resultNumber - 1 - offset + totalNumbers) % totalNumbers; // 0-based index
                const moveDistance = -numberWidth * adjustedResultIndex; // Distance to move in pixels

                console.log('Adjusted Result Index:', adjustedResultIndex);
                console.log('Move Distance:', moveDistance);

                // Set up the animation
                wheel.style.transition = 'transform 3s ease-in-out';
                wheel.style.transform = `translateX(${moveDistance}px)`;

                // Handle the end of the animation
                setTimeout(() => {
                    wheel.style.transition = 'none'; // Disable transition
                    wheel.style.transform = `translateX(${moveDistance}px)`; // Final position

                    // Highlight the result number
                    const sections = wheel.getElementsByClassName('roulette-number');
                    for (const section of sections) {
                        section.classList.remove('highlighted');
                    }
                    document.querySelector(`.roulette-number[data-number="${resultNumber}"]`).classList.add('highlighted');

                    showPopup(`Result Number: ${resultNumber}, Result Color: ${resultColor}`);

                    // Refresh the page after 1 second
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                }, 3000); // Match the transition duration
            } else {
                alert('Something went wrong. Please try again.');
            }
        }, 'json'); // Ensure the response is parsed as JSON
    }


    function showPopup(message) {
        const popup = document.createElement('div');
        popup.className = 'popup';
        popup.innerHTML = `
            <div class="popup-content">
                <p>${message}</p>
                <button id="close-popup">Close</button>
            </div>
        `;
        document.body.appendChild(popup);

        document.getElementById('close-popup').addEventListener('click', function() {
            document.body.removeChild(popup);
        });
    }
});