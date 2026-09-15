<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0f172a">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="icon" type="image/png" href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAEAAAABACAYAAACqaXHeAAAW7UlEQVR4nOVbB3RUddb/Tc9MMkkmmfRCiimEDgooKOgHgooFXBZRUFexfYsN13XVXcHC4tr2CLiiFF13pbiIsqJ0WekIAUJJAklInfQ2mZap7zv3vnkhQCZF+XT37D3nnSSv/P/3f3uLTBAEAf/FIMd/OcjxHwA+QYDHK/znEcB3kXbRn3Q1tLnw2vpy7C9sxbubyuF0++D1ga8ukZTJoFTILljX6xP+/Qkgl8nQ2cR4fAIjf6S4DfsKWxFnUKOqqR0KuQwKOfjqDBLXvzxUj3mrCuHy+DrWpW8uBygvxyISMwgnOq9MBtidXhw8a0Z5vQNjcwzIiNNB5edibLgaD0+IxckKO1QKOf72XQ3+VehERpSAF3+RBq/PB7n8PNcNISpkxAdDrRQplFtiQWWjA1OujGKiyWjDn5MAcv/+xF0ZZPD5BCz5phKNZifCQ1Rod4mc21doRqvNxcR47pMSFNU5sep/s7A5twH/PNKMbS8NE5Hyi8K5OgeSjUEYN8DAh39hdSlarG4oIKCs3o4rrwhFQkQQ70cE+0G440cA6SGJ+No9tfgmt5FFk2DFDhPW7qnG/jNmROpVCNUp8JfNlXh4ZRXqW11weQSMSNdj38KhbAPo3qJ70nDdgHAm4te5DfhsXy0+2FrFUkAq8OH2aozNDsUbs9Pw+C2JqDTL0djmZmnDj9AG2Y+JA0j0ifCbDjegzuzCTcONiI/Q4MVPi3Ci3M4q8OANsfye2+tDcbUNX/1+BJZtrUJVowOl9R4MSQmCTqNAWLAKLRY36sxOmG1eNFpcePaOFD7d/DVF2PT74R37HjzbxvbhmuwwPjsxorOR/EkkwOcjcQcazG5MuSoKeq0S9y8+gfKGdpwx2SGXA/NuTUK/mGCkxuhQUtuOBKMWR87Z8H2RGedqHZg1zoh7xsXhX6daUNPsxJQrjVixrQp/fiATSrkMjywrxIqdNXjr/izY2r1444syjHohD+9vrYYxVAmvV8BtC4+hqMbuZ4jw09kAuV/ndp5sYkM3bXQMiuoFnKqworTOgZdmpCE1Woup7xTj+kw1PnlyIKa8lotpbxbiq99mYk9BC179shEaoRyzxsXh84P1qGvz4rGbkkHHeHdONnuIISl67DrZjNc3nEOb3YvnbkvAtNFRHXjMuDaW7UH/xGDRzfrv99Yuyvt6cInIzVY3TE3tmDoqGqX17Vj4eQlsNgeuTNczV3efbsHgFD3+8WQ6jAYdPj9Qi3EDInDgtRyEBqswOjMc47KC8MQtyRiVGYYWO+BwuLHgrnQ2eGQ76PCrdprwxaF63pcIpVPLMO2NU4h7cC9yS9qQHqtFaV07qwSpAh28L05B1lcbQG+TqK3bW4u8MguyE/XIKzUjQq/C6XIbjld6EKrxYN0zA5EWq2PxfPjDc0gMV0KnFiBXqqCUeRFvUCNUq4DD5YPT44Nep0ZxtRUej4DYSC2Gp4awjkeEqPDRtyb86YsK7FgwHBmP7cbbD+TA0u5FbrEZVc0uaJU+7HzlKsZv4/cNqDc78dDERCZIT/GCrK8EkFzOyh0mHDprxp6CNjx/ZzLuHR+PNrsHn+xpwtSrwhBnCMKnu+tYvwelhmFgkg6ZsRrERWg64oGLgSJBcnOnq+zYW9CK0gYPUo0ylpz3tpjgdAt4ZGIsv0s4TBwSicIqK4LUSmw/aUaIWkCSMYilIs6g6YhJLisBCMggLf2mAvmVNlybE45fjomFVqPoONjavbVMnCGp4ZgwOAyJkUG41H1ecIsRvZhbFPl9faQRJ8ot0KnlePyWZGw60sh2RqOSs9v1QoHyOgsKTE6MzgxBWnQQHp2chN6CrC8EID0jd7P46wro1AqO2PIr7Xh3ThY/L66x4+2NZYg1BOE3t/dDcJCi48AEhHBPHJEMme8i10a24ESZBYeL22AIVmL8ICM0SiDeGAz4PGi1eTA8TY9haaG9Ev0fZASVChkvPig5BKZmB5btbOYgh+DrIw34w+pituLzZ6Tx4SVOi7F+z4cnoHcId+nwtAbZnAf+JwG/n56OycOMCNYoMSQlBG6vgMxYNTYfbWSjnByl5cSqL3mCrC8SQEaPLDMBGcEdp+14f04q3ttcgZJaB16ZeQXCg5Vwe3xQKeV+bgodEaIEdKjeSMPF30gH+/t3Ndh9uhnXD4rEmP7hrHqk8xKQwHGA2Jv1hR7A5/PxT5fHJ0x9PU/4xVunhbPVto7n724qF5756EzH3x6vT/CKn/QaaAtpn57eo/UJVu+uEZ79+MJ98yutwoSXjwnbjjd13OsJ5N0ThzgoY/F6emUhDCEalNW147vTLfycDBLp/Vv3Z7J9IFElLklZYb3ZhfxKa8daUta4YnsVGzb6mzJc0XfLeA3Jn3cFkqEk4zjz2likx+qw6PNz/IxqDE+vKuT8wNTkQGVje8e+3YG8WwL4M71miwuF1S7EGVQYnxOMORMSUFhlw8ffmrB4Tja/Kx5cBrPdg83HmhnZ6mYn1uyp5ece8nGCAIfLi42HGhCuU4q6LgdqW12wOLys93TRWkSDQMhToET6/8ikRDRZPMyI2HANXrs7AzanF8u3mziTpHV60nBlwMOzHglM0e+LrTAGy2B2CHh4Qjw/f3ldCV6emY5lWypx4IwZf31yIHPvmY/OQKXSwOZw4US5FVfE6RgJKWujdVNitJw8VTU5sTG3DfZ2J1xuL1KidTDo1Ug1KjFpmLF7xOWip1g0OwMz3szDgKRgxISrcffYaFTU27B8mwlBKjlHmSSZF9uhHgkAEkvImNIfbjfBanfjrmtjMKifnv38+EERMOpV2FdgxrRrovHosnxEh6lxy4goTB4WgVsW5uGarDDcd308E0YhlzNXKV546tZkzPrzSSRFaXFFXAiWPJiBdrcPewvM/M6GAzUoqaOCRzQSItRdWnVSGToYGcB5t/fD3/5VjZdmpMMQrIA+Wc9B1ZGSNpEAPkAuOqveq4AgCCzO89eUYGSWkcPS6HAN36Nc/Z7r4mAMVcMYpsLVWeGYPT6eN5s6OhoKhRxj+htwusrG1RsSa1F/Rev80U4TgtQKvHJXOloslD3amFsTBhtw4xADlj2aA7PNg/sXF+BkuTVgpkeEIeKO7W9gO3XgTCvuuS4eZ6vt2HysEQWVdsZXVIU+EEDgPJ+MjQBTowP3jI3kxOea7HCs31+LGwZFICRIwQtTXY90fUx2OHOfDBjp6EvTUziEXX+wEb9acgr5VTacq2vH2/+sxDd5Vs71sxOD8ezUVC6J1bY4+VviHNmL5+9MxdO3JmBvgWhwiYtdHkAuMuv+GxI4WDKGqpggkaFaTBxqgN3p69YdKgNxn6x/QaUVMqUaz6024bdTkpnax85ZsHBWRkecfcOgSObo8LRsLn0FqUWaUhzw1BQxJD1easFn+xogk/kwJCUUe14dgmCNnK15WowWw9NCse14E+69Pt6f0YkBV5hOBblMtOaBgBhFtmBoqp5VlmxWTlIw3txYjrX7lLA6vLj7ujjRQ3VBCXl31p+qtw1tXii8Vk5fT1daoVbJEKZTdpSmqS5HSGw8VM+Hl1wYEUhyaYTcghkpmP/LNNwxkiI5MUgiSaHnFNtT6iwdSIoei2psHPP3BIKfaTcOjcSuU80YkR7KOcF94yM5qSqosvF5uvKu8ku5L25uanJynq+WuVj81EoZl7JJ3yXXwsmIT2Bv8Nn+Ony4rUp0Yf68/LxLEwkhXVJKTRKz6PNSjMoIYyJxUZVCYT9WpNM3jxC9gSKAEZPwIKkhG7Q3X1QZSpzuXVKEA4VmVjWS6K4iZPkld/yuimp4JXXtmDAsCu8/2p8Pk19hxdDUUN5MCmXpJ4W/K+cOYEpTAZNS1c7BjFTHly56Qj/X7K1hbk0aFtkRHneGqFA1Dhe1dWsDGGXmrsD1SPImFGtQQeXu62KRFBeOt74s5cN3FWDJL7nj5wLV+CjK++vuZs4BiCiNFjentp2DC3qXYn+y4inRWk6VewI6JiUtpyssXEi9OHuTDksldaUysAW/CG1egw5OQRrZAaezHQnhKtSZ3f5YpBc2wOffjNwfub6FMxI4KGmyutEvSgutWs4clNYicSeDV2iyoaLBweUwsuTdZWRkYkm3R2UYsG5fbUfkJ13S2mkxQTha0sZ/90QE6TkxweLwsf2x2H24/cpQjjoDgTzQg9pWJ6wOJ+avKUarzY3SOjurQOfNGPztL9LnsyY7+2A6OwVQATf1BzHTx8Tg+DkL/rqrmr+RLslOJBm1OHTGzC6YVLA3ktBkceFISSsTtdHSDqVKjdwSs5/7vVEBP4huSsDcm5PZvVA5atwgAz/rjIjIaJlo6Wem49mPz3APkCK07uJw+oy4RNXjmhYnh9YUzZHhI+9A+A7uF4K0WC3mrirDzhPN/F13iRIB5QT1ZjdLGEWSNy46iyen9Ou0aw8EkPnfGZUZDrNdgFIp58Ik6XkgoaZvCDEyaI9MSsKsd05ixY5qtrx0nw56MTGII8RVrVqB301LZUJflRHGzZQ3vyxHeYODpWhkZhiy45Sc7fW2fkAEpP1qWtoxd1I0xxgi9EICZP5dJg83cnHy4121HeXmHXkiF7pChESO3iO3tfihbBTX2HDwjCiKYijcNfZSmkzGKzshGE/f2o+Lmuv313PLjWqLguDDXWNjuy11SfSlqFQyeJUN7bhnjAEtNk+g80MZqOPz1hdluGlYFDITdBwA9U8M4fw7EAEkIlB0R1nZnVdH452N5fjqSBP/PfemRD/yF1pjrgX4AxlOv2TAtNHRHc/JE1ENktSEqj7dZXYEUWFqNtZkk/pFa/Hahmo8OUlcryvlkQda6I7R0RyQkF+n+l6kXsnWm9xXd+1oKZIjnSURnjWOeoMCfve3Ir+/7vo7WlNaVjKCpDqUTl8/KIKJaW33AEL3toXSbCIC/dxf2AKHzYJlWysD44suDkDIkFE7fKaRjcn6/XX+4EfgpqXIsQAL+ouf9D317ylPn3drMrvPLcdEXexp3EUqoFL2SPvcMTKaZwR+/UEB7C6vf9LkwjWk6JGSKmq/i+soEBWm4Xyl1wToHNwsnJ2JNU/nYPo1MYxUfEQQR2a0eaBGJEk5HZCqtzTE8MVBsa01MiOMS2GSTRAN4/nvxOqvqIIXSwU9e+EXqdxs+WRXDRO5syRJ2Su5bofTx5nqK+tKoJQLaLO5cftIsZfY1QyBPFBYmRAZhOwEHb440oY1e2rg9giYOMTImR0h1p1PJm7QGhOGROLT3Sa+R2KcZNTgV0spxz9fI5DmhqRaYpdIysUmCRc+dtdyoUMKnghoL1qDmqgDkoN53etywvmZLkjJ7beAuKILkJCiYsY/9tVg+wkLZ2xDUvVMZerSdhfpiUZKxokT+WIqVuo0ciyalYkZY2Pwpw1lWLC2hAshUjOThiLmLCvmkjftTQeW8JAyRyrGXpOp566zVPruzDhqyN42MpoN5rEyGwanG7lrnBFPZbmu5yjkgQ8AHk0ZkaqF3emGzenh4GZwPz12nGjsEM3ARBDViAoVJDFvf1nO6fLkoRH4+9OD2KMs2VyNx1cWc8GE3N3Lv+yHoyVmJrYUDEmVYKow78hrwtu/ymKXSetLEkT4kvuj/IX0n9Rr96kmbPq+Go9NTup2bkDZ1U3pcNRoFHweBOuCsHhTOdJidFzyenVdCaaOigm46Pl1RDEl90WlKUqnByaFIFSnxOzxcZg9XqwIE7dpFuihpacRG6HhvuOIK8JxVXqImEkq5FixvZKzPYk4dFAJT1Knf+yvxbSrRXdHeH+3cCQmLTjCSRpFsgFxFAL4FKlHR7N81nYvlmw2IT5UgeVzB2AlRXmCD3O4BX3peJsEks/eV9CKF1cXIVijwr3jYzFjrNjh7QwUClMj9IkpyZzQrN3XgPhwpchlmRKhQWJEaXPJcPhsM/ciyEUSnKqw4P3NVRyAbc9rwtIttchJUEOjlGP+jHS2IQFjByFgF0bsqjjdXuHRD84Im440CmOePyLsK2gRrA6PMOudE0JNS7u/q9N998Xr8wlfHW4Q9he2CI8vLxDySi3C0ZI24WiJWTheahEeXHpKWLG9Svj4W5OwYG1xj92cP64/JxwuMnMHivB87P184XBRKz+b+VaecKy0TRj12wPC8dI2//6B15IFkgDmIAUkAI/AvPBpGU5XWTAyPYSLH6SzK3ea8OFjOb3uxpI1pj6Cw02U94rclauRaFDg3vFxbLwoDQ/RqeD1iM8vARlQ3+LC6/dlstos+bqiowL0wdZKbMptRpBGgRi9Au88kO1v2XddC2DojtISd01N7cLYFw4JL68tEm7741Fh8aZyfr58W5Uwb1Uh/+5ye7vt7/XUpyMpkcDt8XV7SUut2VMjPLosn38/WW4RRjxzQLDYXcITywuE3oIc6F2p6fXZmdhwoAEalQK781u56jJnYgIbHOoUU1Gku3aWJCFS/6/zJZXDpG+lFllXl9Q+zyuzctF26UP9Yba5eY3ESA0eWl6OuAhxIKNX88RCL0Bizto9NcLweQeEm1/NE25bdFww29x8f+X2KuHldSW95vYPAVpSWvfT72qEX3+Qz/ds7W5h2LzvhdwSM+N5ssLRp3XlPZPovFucPiYWD05IgM3hxOkKGz7eVc0jcQ9MSIBGKcNTKwt5UkNKjS/HQLeUFBHXad1XPivBjhNNPDvY7vLi1yvOITsxBAvWFvNA1sAkcXT2sg9ICP63KBkhd7V0cyWuTAvBxKFRiA5TcTFky9FGvL+lkglFI23Sd9JEaW8LGlLZvLPqkDumzs/A5BA8datY4Zn59gnYvWqMStciSAVkxetw04gozlV6OyUi6y0BJMSkROm+JfkID1bhWLkDSeEyPjQlTU0WN/64/hyarF5OhScMjrhgDSnVvZgY4j1xbL4zUC1i1c5a1DTZ8YcZ6Rzaku7/ZWs1cotbuFoVGxGMV+9KwRVxWrER2ofBH1lfCEAgxuACj8QePGvByh2VeP7OFDz3yVkMSArBe4/kIFij4KRpzd562BxuZCXqceNQA1KjgzpG3gOu7xNQXOvA0VI7vi9qhdPlxs0jonDLCCOsDg/yyq1M4DkTk/DJLhNnmQ9NTOBGbV+Go34wAS4GKjacKrdhcEoIh7V1rU4kRYXgN7clstWmiu7OU23ILWmF2yvjWT4KfSnUDaEWmd9HUzE0WKtBdZMDDrcMIzNCMT4nFAOTg3mfb080czSaX04T6GpcnWNEhA54cXpa32aCLhcBfFQT8Ikui0SSqkZz3jvJRYjZNyRjf34Dpl8T29HakqCk1o4TZVa0tfugVohlMEZAEDAoJQxZ8RdKyerdNXhjQxmGpelR1ezkxgxx/GyNA/eNF5uefR24uiwEkEASO7L+1ByhyGzq1XFY/V0VZ29U3xudZeDKEEVlA5L10GsvbfS5vYBKAU6RaXT+2Y/OItqg4ewwRCNDjdmLYDXwxJR+uPPqnhOxn4wABJ0LlZSWbjneguomO9uBA2da8Ny0NB6tvXm4EaZmJxbclcbVnQnzc9Fi8wE+N7KSDZg8JIynT6JD1YjQK7HukBWRWhf6J4XycMZrM1O4My2Wvc/PEv7sBJBAcncENP9DyNI/O9BwREqMHmeqzJw9Ln24P0sESUhRjQP7ClpQZZajn4H6j05yfmiwuDF3cjxSY7TsbajG+P8BsstJgM5+v7M7I/WgDvLnB+pYIg6dc2LDb/pzAbO4xgGz3Q0f5Nh6rAl3jjby7DH900VWgpjudv5/pB/x/1E/DQEk6LyqlFNIakLl8kAT4+K357M3qSnTXS/g35IAnUEKfAIdRhqpJQ5L0yk9NUD+owjw7wxy/JeD/OdG4OeG/wP4TcG7xxKLEwAAAABJRU5ErkJggg==">
    <link rel="shortcut icon" type="image/png" href="data:image/png;base64,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">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}?v=transport-logo-blue-20260808">
    <title>{{ auth()->user()?->hasRole('admin') ? 'پنل مدیریت کل' : 'پنل انجمن' }} - سامانه جامع دوزوله</title>

    <script src="{{ asset('assets/js/tailwindcss.js') }}"></script>
    <link rel="stylesheet" href="{{ asset('assets/css/iran-plate.css') }}">
    @vite(['resources/js/app.js'])
    
    <style>
        /* فونت‌های محلی */
        @font-face {
            font-family: 'Vazirmatn';
            src: url('{{ asset('assets/fonts/Vazirmatn-Regular.woff2') }}') format('woff2');
            font-weight: 400;
            font-display: swap;
        }
        @font-face {
            font-family: 'Vazirmatn';
            src: url('{{ asset('assets/fonts/Vazirmatn-Bold.woff2') }}') format('woff2');
            font-weight: 700;
            font-display: swap;
        }
        @font-face {
            font-family: 'Vazirmatn';
            src: url('{{ asset('assets/fonts/Vazirmatn-Black.woff2') }}') format('woff2');
            font-weight: 900;
            font-display: swap;
        }
        html, body, button, input, select, textarea {
            font-family: 'Vazirmatn', Tahoma, sans-serif;
        }
        body {
            background-color: #f8fafc; /* رنگ پس زمینه بسیار ملایم برای محتوا */
        }
        
        /* زیباسازی اسکرول‌بار برای حالت حرفه‌ای */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        .sidebar-tree summary::-webkit-details-marker { display: none; }
        .sidebar-tree .tree-arrow { transition: transform .2s ease; }
        .sidebar-tree[open] .tree-arrow { transform: rotate(180deg); }

        .sidebar-footer { background: transparent; }
        .analog-clock {
            position: relative;
            width: 6.25rem;
            height: 6.25rem;
            flex: 0 0 auto;
            border: 3px solid #dbeafe;
            border-radius: 9999px;
            background: #fff;
            box-shadow: 0 6px 16px rgba(2, 6, 23, .3), inset 0 0 0 1px #cbd5e1;
        }
        .clock-face { position: absolute; inset: 0; width: 100%; height: 100%; }
        .clock-hand {
            position: absolute;
            left: 50%;
            bottom: 50%;
            width: 3px;
            border-radius: 9999px;
            transform-origin: 50% 100%;
        }
        .clock-hour { height: 23%; width: 4px; background: #050505; }
        .clock-minute { height: 32%; width: 3px; background: #050505; }
        .clock-second { height: 38%; width: 1px; background: #ef4444; }
        .clock-pin {
            position: absolute;
            left: 50%;
            top: 50%;
            width: 9px;
            height: 9px;
            border: 2px solid #fff;
            border-radius: 9999px;
            background: #ef4444;
            transform: translate(-50%, -50%);
            box-shadow: 0 1px 3px rgba(15, 23, 42, .35);
        }
        .sidebar-version { display: flex; width: 8rem; align-items: center; justify-content: center; gap: .5rem; white-space: nowrap; font-size: .68rem; font-weight: 900; letter-spacing: .015em; }
        .sidebar-version::before, .sidebar-version::after { content: ''; height: 1px; flex: 1; background: linear-gradient(90deg, transparent, #38bdf8); opacity: .65; }
        .sidebar-version::after { transform: scaleX(-1); }

        #sidebar { width: 16rem; }
        @media (min-width: 768px) {
            #sidebar { position: relative; transform: none !important; width: 5rem; overflow: hidden; }
            #sidebar.sidebar-open { width: 16rem; }
            #sidebar > * { min-width: 16rem; }
            #sidebar:not(.sidebar-open) .h-24 { width: 5rem; min-width: 5rem; padding: 0; }
            #sidebar:not(.sidebar-open) .h-24 img { height: 3rem; }
            #sidebar:not(.sidebar-open) .h-24 div { display: none; }
            #sidebar:not(.sidebar-open) nav { width: 5rem; min-width: 5rem; padding-left: .75rem; padding-right: .75rem; overflow-x: hidden; }
            #sidebar:not(.sidebar-open) nav > a,
            #sidebar:not(.sidebar-open) nav > .pt-4 a { justify-content: center; gap: 0; padding-left: 0; padding-right: 0; font-size: 0; }
            #sidebar:not(.sidebar-open) nav > a svg,
            #sidebar:not(.sidebar-open) nav > .pt-4 a svg { width: 1.25rem; height: 1.25rem; }
            #sidebar:not(.sidebar-open) nav > div:not(.pt-4) button { justify-content: center; padding-left: 0; padding-right: 0; }
            #sidebar:not(.sidebar-open) nav > div:not(.pt-4) button > div { gap: 0; }
            #sidebar:not(.sidebar-open) nav > div:not(.pt-4) button > div span:last-child,
            #sidebar:not(.sidebar-open) nav > div:not(.pt-4) button > svg,
            #sidebar:not(.sidebar-open) #subDozbalaghMenu,
            #sidebar:not(.sidebar-open) #subSettingsMenu,
            #sidebar:not(.sidebar-open) nav > .pt-4 > div:first-child { display: none; }
            #sidebar:not(.sidebar-open) .sidebar-tree summary { justify-content: center; font-size: 0; padding-left: 0; padding-right: 0; }
            #sidebar:not(.sidebar-open) .sidebar-tree summary span:first-child { font-size: 1.1rem; }
            #sidebar:not(.sidebar-open) .sidebar-tree summary span:first-child { max-width: 1.5rem; overflow: hidden; white-space: nowrap; }
            #sidebar:not(.sidebar-open) .sidebar-tree .tree-arrow,
            #sidebar:not(.sidebar-open) .sidebar-tree > div { display: none; }
            #sidebar:not(.sidebar-open) .sidebar-footer { min-width: 5rem; padding: .75rem 0; }
            #sidebar:not(.sidebar-open) .analog-clock { width: 3.25rem; height: 3.25rem; border-width: 3px; }
            #sidebar:not(.sidebar-open) .sidebar-version { display: none; }

            /* Desktop sidebar is temporarily fixed open. */
            #sidebar,
            #sidebar.sidebar-open { width: 16rem; overflow: visible; }
        }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800">

    <div id="sidebarOverlay" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-40 hidden transition-opacity md:hidden" onclick="toggleSidebar()"></div>

    <aside id="sidebar" class="sidebar-open fixed inset-y-0 right-0 z-50 bg-slate-900 text-slate-300 transform transition-all duration-300 translate-x-full flex flex-col shadow-2xl">
        
        <div class="h-24 border-b border-slate-800 flex items-center justify-center px-2 bg-slate-900/50">
            <img src="{{ asset('images/logo1.png') }}" alt="لوگو" class="h-16 w-auto drop-shadow-lg">
            <div class="mr-2 flex flex-col">
                <span class="text-white font-black text-sm tracking-wide">مدیریت دوزوله</span>
                <span class="text-slate-500 text-[10px] font-bold">{{ auth()->user()?->hasRole('admin') ? 'مدیریت کل سامانه' : 'انجمن خراسان رضوی' }}</span>
            </div>
        </div>

        <nav class="flex-1 overflow-y-auto py-6 px-3 space-y-1.5">
            @php
                $panelFeatures = app(\App\Services\PanelFeatureService::class);
                $showAssociationFeature = fn (string $feature) => $panelFeatures->enabledForUser(auth()->user(), $feature);
            @endphp
    
            @if(auth()->user()?->hasRole('association') && $showAssociationFeature('dashboard'))
            <a href="{{ route('association.dashboard') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold text-sm transition-all duration-200 {{ request()->routeIs('association.dashboard') ? 'bg-sky-500 text-white shadow-lg shadow-sky-500/30' : 'hover:bg-slate-800 hover:text-white' }}">🏠 داشبورد انجمن</a>
            @endif

            @if(auth()->user()?->hasRole('association') && $showAssociationFeature('company_header_ticker'))
            <a href="{{ route('association.company-header-ticker.edit') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold text-sm transition-all duration-200 {{ request()->routeIs('association.company-header-ticker.*') ? 'bg-sky-500 text-white shadow-lg shadow-sky-500/30' : 'hover:bg-slate-800 hover:text-white' }}">📢 تابلوی روان شرکت‌ها</a>
            @endif

            @if(auth()->user()?->hasRole('admin') || $showAssociationFeature('admin_dashboard'))
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold text-sm transition-all duration-200 {{ request()->routeIs('admin.dashboard') ? 'bg-sky-500 text-white shadow-lg shadow-sky-500/30' : 'hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-5 h-5 {{ request()->routeIs('admin.dashboard') ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                داشبورد کل
            </a>
            @endif

            @if($showAssociationFeature('shahbaz_company_review'))
            <details class="sidebar-tree rounded-xl" @if(request()->routeIs('association.shahbaz.*', 'admin.shahbaz.*')) open @endif>
                <summary class="flex cursor-pointer list-none items-center justify-between rounded-xl px-4 py-3 text-sm font-bold hover:bg-slate-800 hover:text-white"><span>🛡️ پرونده شرکت و شهباز</span><span class="tree-arrow text-xs">⌄</span></summary>
                <div class="mt-1 mr-3 space-y-1 border-r-2 border-emerald-800 pr-2">
                    <a href="{{ route('association.shahbaz.companies.index') }}" class="block rounded-lg px-4 py-2 text-xs font-bold {{ request()->routeIs('association.shahbaz.*') ? 'bg-slate-800/50 text-emerald-400' : 'text-slate-400 hover:text-white' }}">کنترل و بررسی شرکت‌ها</a>
                    @if(auth()->user()?->hasRole('admin'))
                    <a href="{{ route('admin.shahbaz.settings.edit') }}" class="block rounded-lg px-4 py-2 text-xs font-bold {{ request()->routeIs('admin.shahbaz.*') ? 'bg-slate-800/50 text-emerald-400' : 'text-slate-400 hover:text-white' }}">تنظیمات مراحل شهباز</a>
                    @endif
                </div>
            </details>
            @endif

            @php
                $associationCmrFeatures = [
                    'cmr_documents', 'cmr_issuance', 'cmr_master_data',
                    'cmr_company_settings', 'cmr_financial', 'cmr_reports',
                ];
                $showAssociationCmr = collect($associationCmrFeatures)->contains(
                    fn (string $feature) => $showAssociationFeature($feature)
                );
            @endphp
            @if($showAssociationCmr)
            @php
                $isCmrActive = request()->routeIs('admin.cmr.*');
            @endphp
            <div class="relative">
                <button type="button" onclick="toggleCmrMenu()" class="w-full flex items-center justify-between px-4 py-3 rounded-xl font-bold text-sm transition-all duration-200 {{ $isCmrActive ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/30' : 'hover:bg-slate-800 hover:text-white' }}">
                    <span class="flex items-center gap-3"><span class="text-lg">▣</span><span>مدیریت e-CMR</span></span>
                    <svg id="arrowCmr" class="h-4 w-4 transform transition-transform {{ $isCmrActive ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div id="subCmrMenu" class="{{ $isCmrActive ? '' : 'hidden' }} mt-1 mr-3 space-y-1 border-r-2 border-emerald-700 pr-2">
                    @if($showAssociationFeature('cmr_documents'))
                    <a href="{{ route('admin.cmr.index') }}" class="block rounded-lg px-4 py-2 text-xs font-bold {{ request()->routeIs('admin.cmr.index') && !request('scope') ? 'bg-slate-800/50 text-emerald-400' : 'text-slate-400 hover:text-white' }}">کارتابل اسناد</a>
                    <a href="{{ route('admin.cmr.index',['scope'=>'drafts']) }}" class="block rounded-lg px-4 py-2 text-xs font-bold text-slate-400 hover:text-white">پیش‌نویس‌ها</a>
                    <a href="{{ route('admin.cmr.index',['scope'=>'active']) }}" class="block rounded-lg px-4 py-2 text-xs font-bold text-slate-400 hover:text-white">حمل‌های در جریان</a>
                    <a href="{{ route('admin.cmr.index',['scope'=>'archive']) }}" class="block rounded-lg px-4 py-2 text-xs font-bold text-slate-400 hover:text-white">تحویل‌شده و بایگانی</a>
                    @endif
                    @if($showAssociationFeature('cmr_issuance'))
                    <a href="{{ route('admin.cmr.create') }}" class="block rounded-lg px-4 py-2 text-xs font-bold {{ request()->routeIs('admin.cmr.create') ? 'bg-slate-800/50 text-emerald-400' : 'text-slate-400 hover:text-white' }}">صدور e-CMR</a>
                    <a href="{{ route('admin.cmr.help') }}" class="block rounded-lg px-4 py-2 text-xs font-bold {{ request()->routeIs('admin.cmr.help') ? 'bg-slate-800/50 text-emerald-400' : 'text-slate-400 hover:text-white' }}">راهنمای فارسی صدور</a>
                    @endif
                    @if($showAssociationFeature('cmr_master_data'))
                    <a href="{{ route('admin.cmr.master-data.index') }}" class="block rounded-lg px-4 py-2 text-xs font-bold {{ request()->routeIs('admin.cmr.master-data.*') ? 'bg-slate-800/50 text-emerald-400' : 'text-slate-400 hover:text-white' }}">اطلاعات پایه</a>
                    @endif
                    @if($showAssociationFeature('cmr_company_settings'))
                    <a href="{{ route('admin.cmr.company-settings.index') }}" class="block rounded-lg px-4 py-2 text-xs font-bold {{ request()->routeIs('admin.cmr.company-settings.*') ? 'bg-slate-800/50 text-emerald-400' : 'text-slate-400 hover:text-white' }}">شماره‌ها و قالب چاپ</a>
                    @endif
                    @if($showAssociationFeature('cmr_financial'))
                    <a href="{{ route('admin.cmr.settings') }}" class="block rounded-lg px-4 py-2 text-xs font-bold {{ request()->routeIs('admin.cmr.settings*') ? 'bg-slate-800/50 text-emerald-400' : 'text-slate-400 hover:text-white' }}">تعرفه و مالی</a>
                    @endif
                    @if($showAssociationFeature('cmr_reports'))
                    <a href="{{ route('admin.cmr.reports.index') }}" class="block rounded-lg px-4 py-2 text-xs font-bold {{ request()->routeIs('admin.cmr.reports.*') ? 'bg-slate-800/50 text-emerald-400' : 'text-slate-400 hover:text-white' }}">گزارش‌های e-CMR</a>
                    @endif
                </div>
            </div>
            @endif

            @if($showAssociationFeature('requests') || $showAssociationFeature('issuance') || $showAssociationFeature('transit') || $showAssociationFeature('archive'))
            <div class="relative">
                @php 
                    $isDozbalaghActive = request()->routeIs('association.pending.*', 'association.approved.*', 'association.transit.*', 'association.archive.*', 'association.permits.*', 'association.courier.*');
                @endphp
                <button onclick="toggleDozbalaghMenu()" class="w-full flex items-center justify-between px-4 py-3 rounded-xl font-bold text-sm transition-all duration-200 {{ $isDozbalaghActive ? 'bg-slate-800 text-white' : 'hover:bg-slate-800 hover:text-white' }}">
                    <div class="flex items-center gap-3">
                        <span class="text-lg">💼</span>
                        <span>کارتابل درخواست‌ها</span>
                    </div>
                    <svg id="arrowDozbalagh" class="w-4 h-4 transform transition-transform duration-200 {{ $isDozbalaghActive ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>

                <div id="subDozbalaghMenu" class="{{ $isDozbalaghActive ? '' : 'hidden' }} mt-1 mr-3 pr-2 border-r-2 border-slate-800 space-y-1">
                    <a href="{{ route('association.permits.index', ['scope'=>'active']) }}" class="flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-bold transition-all {{ request()->routeIs('association.permits.*') && request('scope','active') !== 'archive' ? 'text-sky-400 bg-slate-800/50' : 'text-slate-400 hover:text-white' }}">
                        <span>💼</span> در حال اقدام
                    </a>
                    <a href="{{ route('association.permits.index', ['scope'=>'archive']) }}" class="flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-bold transition-all {{ request()->routeIs('association.permits.*') && request('scope') === 'archive' ? 'text-emerald-400 bg-slate-800/50' : 'text-slate-400 hover:text-white' }}">
                        <span>🗂️</span> بایگانی
                    </a>
                    @if(false)
                    @if($showAssociationFeature('requests'))
                    <a href="/web/association/driver/list" class="flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-bold transition-all {{ request()->is('web/association/driver/list') ? 'text-sky-400 bg-slate-800/50' : 'text-slate-400 hover:text-white' }}">
                        <span>⏳</span> درخواست‌های معلق
                    </a>
                    @endif
                    @if($showAssociationFeature('issuance'))
                    <a href="/web/association/approved/permits" class="flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-bold transition-all {{ request()->is('web/association/approved/permits') ? 'text-sky-400 bg-slate-800/50' : 'text-slate-400 hover:text-white' }}">
                        <span>✍️</span> صدور و تخصیص سریال
                    </a>
                    @endif
                    @if($showAssociationFeature('transit'))
                    <a href="/web/association/transit-permits" class="flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-bold transition-all {{ request()->is('web/association/transit-permits') ? 'text-sky-400 bg-slate-800/50' : 'text-slate-400 hover:text-white' }}">
                        <span>🚚</span> مدیریت تردد
                    </a>
                    @endif
                    @if($showAssociationFeature('archive'))
                    <a href="/web/association/permits/archive" class="flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-bold transition-all {{ request()->is('web/association/permits/archive') ? 'text-sky-400 bg-slate-800/50' : 'text-slate-400 hover:text-white' }}">
                        <span>🗂️</span> بایگانی کل پروانه‌ها
                    </a>
                    @endif
                    @endif
                </div>
            </div>
            @endif

            @if($showAssociationFeature('reports') || $showAssociationFeature('performance_reports') || $showAssociationFeature('financial'))
            <details class="sidebar-tree rounded-xl" @if(request()->routeIs('association.reports.*', 'association.performance-reports.*', 'association.issued-financial.*')) open @endif>
                <summary class="flex cursor-pointer list-none items-center justify-between rounded-xl px-4 py-3 text-sm font-bold hover:bg-slate-800 hover:text-white"><span>📊 گزارش‌های انجمن</span><span class="tree-arrow text-xs">⌄</span></summary>
                <div class="mt-1 mr-3 space-y-1 border-r-2 border-slate-800 pr-2">
            @if($showAssociationFeature('reports'))
            <a href="{{ route('association.reports.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold text-sm transition-all duration-200 {{ request()->routeIs('association.reports.index') ? 'bg-sky-500 text-white shadow-lg shadow-sky-500/30' : 'hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-5 h-5 {{ request()->routeIs('association.reports.index') ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-6m4 6V7m4 10v-3M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                گزارش دوزوله‌ها
            </a>
            @if($showAssociationFeature('performance_reports'))
            <a href="{{ route('association.performance-reports.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all {{ request()->routeIs('association.performance-reports.*') ? 'bg-violet-600 text-white shadow-lg shadow-violet-600/30' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <span>🧾</span> گزارش عملکرد کاربران
            </a>
            @endif
            @endif

            @if($showAssociationFeature('financial'))
            <a href="{{ route('association.issued-financial.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold text-sm transition-all duration-200 {{ request()->routeIs('association.issued-financial.*') ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/30' : 'hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-5 h-5 {{ request()->routeIs('association.issued-financial.*') ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M5 7h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V9a2 2 0 012-2zm2-4h10a2 2 0 012 2v2H5V5a2 2 0 012-2z"></path>
                </svg>
                مبالغ دوزوله‌های صادرشده
            </a>
            @endif
                </div>
            </details>
            @endif

            @if($showAssociationFeature('companies') || auth()->user()?->hasRole('admin') || $showAssociationFeature('crm'))
            <details class="sidebar-tree rounded-xl" @if(request()->routeIs('admin.companies.*', 'admin.association-users.*', 'admin.reports.pwa-installations.*', 'admin.association_crm.*')) open @endif>
                <summary class="flex cursor-pointer list-none items-center justify-between rounded-xl px-4 py-3 text-sm font-bold hover:bg-slate-800 hover:text-white"><span>🏢 اعضا و ارتباطات</span><span class="tree-arrow text-xs">⌄</span></summary>
                <div class="mt-1 mr-3 space-y-1 border-r-2 border-slate-800 pr-2">
            @if($showAssociationFeature('companies'))
            <a href="{{ route('admin.companies.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold text-sm transition-all duration-200 {{ request()->routeIs('admin.companies.*') ? 'bg-sky-500 text-white shadow-lg shadow-sky-500/30' : 'hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-5 h-5 {{ request()->routeIs('admin.companies.*') ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                مدیریت شرکت‌ها
            </a>
            @endif

            @if(auth()->user()?->hasRole('admin'))
            <a href="{{ route('admin.association-users.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold text-sm transition-all duration-200 {{ request()->routeIs('admin.association-users.*') ? 'bg-violet-600 text-white shadow-lg shadow-violet-600/30' : 'hover:bg-slate-800 hover:text-white' }}">👥 مدیریت کاربران انجمن</a>
            <a href="{{ route('admin.reports.pwa-installations.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold text-sm transition-all duration-200 {{ request()->routeIs('admin.reports.pwa-installations.*') ? 'bg-cyan-600 text-white shadow-lg shadow-cyan-600/30' : 'hover:bg-slate-800 hover:text-white' }}">📱 وضعیت اپلیکیشن‌ها</a>
            @endif

            @if($showAssociationFeature('crm'))
            <a href="{{ route('admin.association_crm.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold text-sm transition-all duration-200 {{ request()->routeIs('admin.association_crm.*') ? 'bg-sky-500 text-white shadow-lg shadow-sky-500/30' : 'hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-5 h-5 {{ request()->routeIs('admin.association_crm.*') ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h8M8 14h5m-9 7l3.5-3.5H19a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10.5a2 2 0 002 2H4v3.5z"></path></svg>
                CRM انجمن
            </a>
            @endif
                </div>
            </details>
            @endif

            @if($showAssociationFeature('countries') || $showAssociationFeature('inventory') || $showAssociationFeature('allocations'))
            <details class="sidebar-tree rounded-xl" @if(request()->routeIs('admin.countries.*', 'admin.inventory.*', 'admin.allocations.*')) open @endif>
                <summary class="flex cursor-pointer list-none items-center justify-between rounded-xl px-4 py-3 text-sm font-bold hover:bg-slate-800 hover:text-white"><span>📦 منابع و سهمیه</span><span class="tree-arrow text-xs">⌄</span></summary>
                <div class="mt-1 mr-3 space-y-1 border-r-2 border-slate-800 pr-2">
            @if($showAssociationFeature('countries'))
            <a href="{{ route('admin.countries.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold text-sm transition-all duration-200 {{ request()->routeIs('admin.countries.*') ? 'bg-sky-500 text-white shadow-lg shadow-sky-500/30' : 'hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-5 h-5 {{ request()->routeIs('admin.countries.*') ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                کشورها و مرزها
            </a>
            @endif

            @if($showAssociationFeature('inventory'))
            <a href="{{ route('admin.inventory.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold text-sm transition-all duration-200 {{ request()->routeIs('admin.inventory.*') ? 'bg-sky-500 text-white shadow-lg shadow-sky-500/30' : 'hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-5 h-5 {{ request()->routeIs('admin.inventory.*') ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                انبار سریال دوزوله
            </a>
            @endif

            @if($showAssociationFeature('allocations'))
            <a href="{{ route('admin.allocations.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold text-sm transition-all duration-200 {{ request()->routeIs('admin.allocations.*') ? 'bg-sky-500 text-white shadow-lg shadow-sky-500/30' : 'hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-5 h-5 {{ request()->routeIs('admin.allocations.*') ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                تخصیص سهمیه‌ها
            </a>
            @endif
                </div>
            </details>
            @endif

            @if($showAssociationFeature('drivers') || $showAssociationFeature('tracking') || $showAssociationFeature('fleets') || $showAssociationFeature('fleet_loading_types') || $showAssociationFeature('cargo_rules') || $showAssociationFeature('driver_announcements'))
            <details class="sidebar-tree rounded-xl" @if(request()->routeIs('admin.drivers.*', 'admin.tracking.*', 'admin.fleets.*', 'admin.fleet-loading-types.*', 'admin.cargo_rules.*', 'driver-announcements.*')) open @endif>
                <summary class="flex cursor-pointer list-none items-center justify-between rounded-xl px-4 py-3 text-sm font-bold hover:bg-slate-800 hover:text-white"><span>🚚 رانندگان و ناوگان</span><span class="tree-arrow text-xs">⌄</span></summary>
                <div class="mt-1 mr-3 space-y-1 border-r-2 border-slate-800 pr-2">

                @if($showAssociationFeature('drivers'))
                <a href="{{ route('admin.drivers.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold text-sm transition-all duration-200 {{ request()->routeIs('admin.drivers.*') ? 'bg-sky-500 text-white shadow-lg shadow-sky-500/30' : 'hover:bg-slate-800 hover:text-white' }}">
                    <svg class="w-5 h-5 {{ request()->routeIs('admin.drivers.*') ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    مدیریت رانندگان
                </a>
                @endif

                @if($showAssociationFeature('tracking'))
                <a href="{{ route('admin.tracking.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold text-sm transition-all duration-200 {{ request()->routeIs('admin.tracking.*') ? 'bg-sky-500 text-white shadow-lg shadow-sky-500/30' : 'hover:bg-slate-800 hover:text-white' }}">📍 ردیابی رانندگان</a>
                @endif

                @if($showAssociationFeature('driver_announcements'))
                <a href="{{ route('driver-announcements.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold text-sm transition-all duration-200 {{ request()->routeIs('driver-announcements.*') ? 'bg-sky-500 text-white shadow-lg shadow-sky-500/30' : 'hover:bg-slate-800 hover:text-white' }}">📢 اطلاع‌رسانی به رانندگان</a>
                @endif

                @if($showAssociationFeature('fleets'))
                <a href="{{ route('admin.fleets.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold text-sm transition-all duration-200 {{ request()->routeIs('admin.fleets.*') ? 'bg-sky-500 text-white shadow-lg shadow-sky-500/30' : 'hover:bg-slate-800 hover:text-white' }}">
                    <svg class="w-5 h-5 {{ request()->routeIs('admin.fleets.*') ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                    مدیریت ناوگان
                </a>
                @endif

                @if($showAssociationFeature('fleet_loading_types'))
                <a href="{{ route('admin.fleet-loading-types.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold text-sm transition-all duration-200 {{ request()->routeIs('admin.fleet-loading-types.*') ? 'bg-sky-500 text-white shadow-lg shadow-sky-500/30' : 'hover:bg-slate-800 hover:text-white' }}">
                    <span class="text-lg">🧰</span>
                    <span>تنظیمات انواع بارگیر</span>
                </a>
                @endif

                @if($showAssociationFeature('cargo_rules'))
                <a href="{{ route('admin.cargo_rules.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold text-sm transition-all duration-200 {{ request()->routeIs('admin.cargo_rules.*') ? 'bg-sky-500 text-white shadow-lg shadow-sky-500/30' : 'hover:bg-slate-800 hover:text-white' }}">
                    <svg class="w-5 h-5 {{ request()->routeIs('admin.cargo_rules.*') ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    قوانین مدارک دوزوله
                </a>
                @endif
                </div>
            </details>
            @endif

            @if($showAssociationFeature('admin_financial') || $showAssociationFeature('system_map'))
            <details class="sidebar-tree rounded-xl" @if(request()->routeIs('admin.financial.*', 'system.map')) open @endif>
                <summary class="flex cursor-pointer list-none items-center justify-between rounded-xl px-4 py-3 text-sm font-bold hover:bg-slate-800 hover:text-white"><span>🛡️ مدیریت کل</span><span class="tree-arrow text-xs">⌄</span></summary>
                <div class="mt-1 mr-3 space-y-1 border-r-2 border-slate-800 pr-2">
            @if($showAssociationFeature('admin_financial'))
            <a href="{{ route('admin.financial.dashboard') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold text-sm transition-all duration-200 {{ request()->routeIs('admin.financial.*') ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/30' : 'hover:bg-slate-800 hover:text-white' }}">💳 مدیریت مالی کل</a>
            @endif

            @if($showAssociationFeature('system_map'))
            <a href="{{ route('system.map') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold text-sm transition-all duration-200 {{ request()->routeIs('system.map') ? 'bg-sky-500 text-white shadow-lg shadow-sky-500/30' : 'hover:bg-slate-800 hover:text-white' }}">🗺️ نقشه جامع سیستم</a>
            @endif
                </div>
            </details>
            @endif

            @if(auth()->user()?->hasRole('admin') || $showAssociationFeature('print_layouts') || $showAssociationFeature('driver_device_reset') || $showAssociationFeature('account_security'))
            <div class="relative pt-2 mt-2 border-t border-slate-800">
                @php $isSettingsActive = request()->routeIs('association.print-layouts.*', 'admin.settings.panel-features.*', 'admin.mobile-app.versions.*', 'driver-device-reset.*', 'association.account.security.*'); @endphp
                <button onclick="toggleSettingsMenu()" class="w-full flex items-center justify-between px-4 py-3 rounded-xl font-bold text-sm transition-all duration-200 {{ $isSettingsActive ? 'bg-slate-800 text-white' : 'hover:bg-slate-800 hover:text-white' }}">
                    <div class="flex items-center gap-3"><span class="text-lg">⚙️</span><span>تنظیمات</span></div>
                    <svg id="arrowSettings" class="w-4 h-4 transform transition-transform duration-200 {{ $isSettingsActive ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </button>
                <div id="subSettingsMenu" class="{{ $isSettingsActive ? '' : 'hidden' }} mt-1 mr-3 pr-2 border-r-2 border-slate-800 space-y-1">
                    @if($showAssociationFeature('print_layouts'))
                    <a href="{{ route('association.print-layouts.index') }}" class="flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-bold transition-all {{ request()->routeIs('association.print-layouts.*') ? 'text-sky-400 bg-slate-800/50' : 'text-slate-400 hover:text-white' }}"><span>🖨️</span> تنظیمات چاپ دوزوله</a>
                    @endif
                    @if($showAssociationFeature('driver_device_reset'))
                    <a href="{{ route('driver-device-reset.index') }}" class="flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-bold transition-all {{ request()->routeIs('driver-device-reset.*') ? 'text-sky-400 bg-slate-800/50' : 'text-slate-400 hover:text-white' }}"><span>📱</span> دستگاه اپلیکیشن رانندگان</a>
                    @endif
                    @if(auth()->user()?->hasRole('association') && $showAssociationFeature('account_security'))
                    <a href="{{ route('association.account.security.edit') }}" class="flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-bold transition-all {{ request()->routeIs('association.account.security.*') ? 'text-sky-400 bg-slate-800/50' : 'text-slate-400 hover:text-white' }}"><span>🔐</span> تغییر رمز ورود</a>
                    @endif
                    @if(auth()->user()?->hasRole('admin'))
                    <a href="{{ route('admin.mobile-app.versions.index') }}" class="flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-bold transition-all {{ request()->routeIs('admin.mobile-app.versions.*') ? 'text-sky-400 bg-slate-800/50' : 'text-slate-400 hover:text-white' }}"><span>🔄</span> نسخه و بروزرسانی اپ</a>
                    <a href="{{ route('admin.settings.panel-features.index') }}" class="flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-bold transition-all {{ request()->routeIs('admin.settings.panel-features.*') ? 'text-sky-400 bg-slate-800/50' : 'text-slate-400 hover:text-white' }}"><span>🔐</span> دسترسی پنل‌ها</a>
                    @endif
                </div>
            </div>
            @endif
        </nav>

        <footer class="sidebar-footer shrink-0 px-4 py-3">
            <div class="flex flex-col items-center justify-center gap-2">
                <div class="analog-clock" role="img" aria-label="ساعت عقربه‌ای زمان محلی">
                    <svg class="clock-face" viewBox="0 0 100 100" aria-hidden="true"><g fill="#111827" font-family="Arial, sans-serif" font-size="11" text-anchor="middle"><text x="50" y="14">12</text><text x="70" y="20">1</text><text x="84" y="35">2</text><text x="90" y="54">3</text><text x="83" y="73">4</text><text x="68" y="87">5</text><text x="50" y="94">6</text><text x="31" y="87">7</text><text x="17" y="73">8</text><text x="10" y="54">9</text><text x="16" y="35">10</text><text x="30" y="20">11</text></g></svg>
                    <span class="clock-hand clock-hour" data-clock-hour></span>
                    <span class="clock-hand clock-minute" data-clock-minute></span>
                    <span class="clock-hand clock-second" data-clock-second></span>
                    <span class="clock-pin"></span>
                </div>
                <div class="sidebar-version text-center text-[10px] font-bold text-sky-400">نسخه آزمایشی</div>
            </div>
        </footer>
    </aside>

    <main class="flex-1 flex flex-col min-w-0 overflow-hidden relative">
        @php
            $panelUser = auth()->user();
            $isAdminPanel = $panelUser?->hasRole('admin');
            $panelDisplayName = $isAdminPanel ? 'مدیریت کل سامانه' : 'انجمن خراسان رضوی';
            $panelUserLabel = $panelUser?->username ?: ($isAdminPanel ? 'مدیر سیستم' : 'کاربر انجمن');
        @endphp
        
        <header class="h-20 bg-white/80 backdrop-blur-md border-b border-slate-200 flex items-center justify-between px-4 sm:px-6 lg:px-8 z-30 sticky top-0">
            
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" aria-label="باز و بسته کردن منو" aria-controls="sidebar" aria-expanded="true" class="md:hidden p-2 text-slate-500 hover:text-slate-700 hover:bg-slate-100 rounded-lg transition-colors focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
                <h1 class="text-lg sm:text-xl font-black text-slate-800 hidden sm:block">@yield('header_title', auth()->user()?->hasRole('admin') ? 'مدیریت کل سامانه' : 'پنل انجمن')</h1>
            </div>

            <div class="flex items-center gap-3 sm:gap-4">
                <a href="https://cits.rmto.ir" target="_blank" class="hidden lg:flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-sky-600 bg-slate-100 hover:bg-sky-50 px-3 py-2 rounded-lg transition-colors border border-slate-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path></svg>
                    سایت دوزوله راهداری
                </a>
                
                <div class="hidden sm:flex items-center rounded-lg border border-slate-200 overflow-hidden shadow-sm">
                    <div class="bg-indigo-50/50 text-indigo-700 px-3 py-2 flex items-center gap-2 text-xs sm:text-sm font-bold border-l border-indigo-100/50">
                        <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        {{ verta()->format('%A %d %B %Y') }}
                    </div>
                    <div class="bg-slate-50 text-slate-500 px-3 py-2 flex items-center gap-1.5 text-xs font-bold font-mono" dir="ltr">
                        {{ now()->format('d M Y') }}
                    </div>
                </div>
                <div class="relative">
                    <button onclick="toggleDropdown()" id="userMenuButton" class="h-10 w-10 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-500 shadow-sm hover:bg-slate-200 cursor-pointer transition-colors focus:outline-none" title="پروفایل مدیر">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    </button>
                    
                    <div id="userDropdown" class="hidden absolute -left-4 top-[calc(100%+1.25rem)] w-[18rem] max-w-[calc(100vw-1rem)] overflow-hidden bg-white z-50 transition-all">
                        <div class="px-6 pb-4 pt-5 text-center">
                            <div class="relative mx-auto w-fit">
                                <div class="flex h-24 w-24 items-center justify-center overflow-hidden rounded-full bg-indigo-50 text-indigo-400 ring-2 ring-slate-200 ring-offset-2 ring-offset-white">
                                    <svg class="mt-5 h-20 w-20" viewBox="0 0 100 100" fill="currentColor" aria-hidden="true"><circle cx="50" cy="31" r="22"/><path d="M10 96c1-25 17-39 40-39s39 14 40 39z"/></svg>
                                </div>
                                <button type="button" onclick="toggleDropdown()" aria-label="بستن پنل کاربر" class="absolute -bottom-1 -left-2 flex h-8 w-8 items-center justify-center rounded-full bg-white text-rose-600 ring-1 ring-slate-200 transition hover:bg-rose-50">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                </button>
                            </div>
                            <p class="mt-4 truncate text-sm font-black text-slate-800">{{ $panelDisplayName }}</p>
                            <p class="mt-2 text-xs text-slate-400">{{ $panelUserLabel }}</p>
                            <p class="mt-1 text-[11px] text-slate-400">{{ $isAdminPanel ? 'مدیر سیستم' : 'کاربر انجمن' }}</p>
                        </div>

                        <div class="border-y border-slate-100 py-1 text-center">
                            <a href="{{ $isAdminPanel ? route('dashboard') : route('association.dashboard') }}" class="block px-5 py-3 text-xs font-bold text-slate-500 transition hover:bg-slate-50 hover:text-sky-700">داشبورد {{ $isAdminPanel ? 'مدیریت' : 'انجمن' }}</a>
                            @if($isAdminPanel)
                                <a href="{{ route('admin.account.security.edit') }}" class="block px-5 py-3 text-xs font-bold text-slate-500 transition hover:bg-slate-50 hover:text-sky-700">تغییر رمز عبور</a>
                                <a href="{{ route('admin.settings.panel-features.index') }}" class="block px-5 py-3 text-xs font-bold text-slate-500 transition hover:bg-slate-50 hover:text-sky-700">تنظیمات سامانه</a>
                            @elseif($showAssociationFeature('account_security'))
                                <a href="{{ route('association.account.security.edit') }}" class="block px-5 py-3 text-xs font-bold text-slate-500 transition hover:bg-slate-50 hover:text-sky-700">تنظیمات امنیتی و حساب</a>
                            @endif
                        </div>

                        <form action="{{ route('logout') }}" method="POST" class="flex flex-col items-center px-5 py-4">
                            @csrf
                            <button type="submit" class="group flex h-16 w-16 items-center justify-center rounded-full border border-rose-500 bg-white text-rose-600 transition hover:bg-rose-600 hover:text-white hover:shadow-lg" aria-label="خروج از سامانه">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                            </button>
                            <span class="mt-2 text-xs font-black text-rose-600">خروج از سامانه</span>
                        </form>
                    </div>
                </div>

            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 scroll-smooth relative">
            @yield('content')
        </div>
        
    </main>

    @if(app(\App\Services\PanelFeatureService::class)->enabledForUser(auth()->user(), 'assistant'))
        @include('components.assistant.chat')
    @endif

    <script src="{{ asset('assets/js/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/js/iran-plate.js') }}"></script>
    <script src="{{ asset('assets/js/pwa-tracker.js') }}"></script>
    
    <script>
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');

        function updateAnalogClock() {
            const now = new Date();
            const seconds = now.getSeconds() + now.getMilliseconds() / 1000;
            const minutes = now.getMinutes() + seconds / 60;
            const hours = (now.getHours() % 12) + minutes / 60;

            document.querySelector('[data-clock-hour]')?.style.setProperty('transform', `translateX(-50%) rotate(${hours * 30}deg)`);
            document.querySelector('[data-clock-minute]')?.style.setProperty('transform', `translateX(-50%) rotate(${minutes * 6}deg)`);
            document.querySelector('[data-clock-second]')?.style.setProperty('transform', `translateX(-50%) rotate(${seconds * 6}deg)`);
        }

        updateAnalogClock();
        window.setInterval(updateAnalogClock, 1000);

        function toggleSidebar() {
            const desktop = window.matchMedia('(min-width: 768px)').matches;
            if (desktop) {
                return;
            } else {
                sidebar.classList.toggle('translate-x-full');
                overlay.classList.toggle('hidden');
            }
            const open = desktop ? sidebar.classList.contains('sidebar-open') : !sidebar.classList.contains('translate-x-full');
            document.querySelectorAll('[aria-controls="sidebar"]').forEach(button => button.setAttribute('aria-expanded', String(open)));
        }
        
        function toggleDropdown() {
            document.getElementById('userDropdown').classList.toggle('hidden');
        }

        function toggleCmrMenu() {
            const menu = document.getElementById('subCmrMenu');
            const arrow = document.getElementById('arrowCmr');
            menu.classList.toggle('hidden');
            arrow.classList.toggle('rotate-180');
        }
        
        // 🔒 تابع باز و بسته شدن آکاردئونی منوی دوزوله
        function toggleDozbalaghMenu() {
            const menu = document.getElementById('subDozbalaghMenu');
            const arrow = document.getElementById('arrowDozbalagh');
            
            if (menu.classList.contains('hidden')) {
                menu.classList.remove('hidden');
                arrow.classList.add('rotate-180');
            } else {
                menu.classList.add('hidden');
                arrow.classList.remove('rotate-180');
            }
        }

        function toggleSettingsMenu() {
            const menu = document.getElementById('subSettingsMenu');
            const arrow = document.getElementById('arrowSettings');
            if (menu.classList.contains('hidden')) {
                menu.classList.remove('hidden');
                arrow.classList.add('rotate-180');
            } else {
                menu.classList.add('hidden');
                arrow.classList.remove('rotate-180');
            }
        }

        document.addEventListener('click', function(event) {
            const button = document.getElementById('userMenuButton');
            const dropdown = document.getElementById('userDropdown');
            if (button && dropdown && !button.contains(event.target) && !dropdown.contains(event.target)) {
                dropdown.classList.add('hidden');
            }
        });

        async function copyInlineText(text) {
            if (!text || ['ثبت نشده', '—', '---'].includes(text)) return;
            try {
                await navigator.clipboard.writeText(text);
            } catch (error) {
                const input = document.createElement('textarea');
                input.value = text;
                input.style.position = 'fixed';
                input.style.opacity = '0';
                document.body.appendChild(input);
                input.select();
                document.execCommand('copy');
                input.remove();
            }

            const toast = document.createElement('div');
            toast.textContent = 'کپی شد';
            toast.className = 'fixed bottom-5 left-5 z-[9999] rounded-xl bg-slate-900 px-4 py-2 text-xs font-black text-white shadow-lg';
            document.body.appendChild(toast);
            window.setTimeout(() => toast.remove(), 1400);
        }

        document.addEventListener('click', function(event) {
            const target = event.target.closest('[data-copy-text], [data-copy-dynamic]');
            if (!target) return;
            const text = (target.dataset.copyText || target.textContent || '').trim();
            copyInlineText(text);
        });
    </script>
    
    @if(request()->routeIs('admin.cmr.*'))
        @include('CMR.admin.partials.feedback')
    @endif
    @yield('scripts')
</body>
</html>
