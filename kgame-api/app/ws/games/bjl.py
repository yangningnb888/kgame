"""
百家乐 (BJL) 游戏逻辑
gtype = 8, 下注位: player / banker / tie / player_pair / banker_pair
"""

import random
from typing import List
from .base import GameBase, GameState


class BJLGame(GameBase):
    gtype = 8
    game_name = "BJL"

    CARD_VALUES = {"A": 1, "2": 2, "3": 3, "4": 4, "5": 5, "6": 6,
                   "7": 7, "8": 8, "9": 9, "10": 0, "J": 0, "Q": 0, "K": 0}
    SUITS = ["♠", "♥", "♦", "♣"]
    RANKS = ["A", "2", "3", "4", "5", "6", "7", "8", "9", "10", "J", "Q", "K"]

    def __init__(self, send_callback):
        super().__init__(send_callback)
        self.shoe = []  # 牌靴

    def get_bet_time(self) -> int:
        return 15  # 15 秒下注

    def get_result_time(self) -> int:
        return 8   # 8 秒展示结果

    def get_positions(self) -> List[str]:
        return ["player", "banker", "tie", "player_pair", "banker_pair"]

    def _draw_card(self):
        if len(self.shoe) < 10:
            self._reshuffle()
        return self.shoe.pop()

    def _reshuffle(self):
        self.shoe = [(rank, suit) for suit in self.SUITS for rank in self.RANKS] * 6
        random.shuffle(self.shoe)

    def _card_value(self, rank: str) -> int:
        return self.CARD_VALUES.get(rank, 0)

    def _hand_value(self, cards: list) -> int:
        total = sum(self._card_value(r) for r, _ in cards)
        return total % 10

    def _should_player_draw(self, player_val: int, banker_val: int) -> bool:
        if player_val >= 6:
            return False
        if player_val <= 5:
            return True
        return False

    def _should_banker_draw(self, banker_val: int, player_val: int, player_third: int or None) -> bool:
        if banker_val >= 7:
            return False
        if player_third is None:
            return banker_val <= 5
        # 百家乐第三张牌规则
        if banker_val <= 2:
            return True
        if banker_val == 3:
            return player_third != 8
        if banker_val == 4:
            return player_third in [2, 3, 4, 5, 6, 7]
        if banker_val == 5:
            return player_third in [4, 5, 6, 7]
        if banker_val == 6:
            return player_third in [6, 7]
        return False

    def _calculate_result(self) -> dict:
        """发牌并返回结果"""
        self._reshuffle()

        player_hand = [self._draw_card(), self._draw_card()]
        banker_hand = [self._draw_card(), self._draw_card()]

        p_val = self._hand_value(player_hand)
        b_val = self._hand_value(banker_hand)

        # 是否补牌
        player_third = None
        banker_third = None

        # 先检查 natural (8 或 9)
        if p_val < 8 and b_val < 8:
            if self._should_player_draw(p_val, b_val):
                player_third = self._draw_card()
                player_hand.append(player_third)
                p_val = self._hand_value(player_hand)

            if self._should_banker_draw(b_val, p_val,
                                        self._card_value(player_third[0]) if player_third else None):
                banker_third = self._draw_card()
                banker_hand.append(banker_third)
                b_val = self._hand_value(banker_hand)

        # 判断胜负
        if p_val > b_val:
            winner = "player"
        elif b_val > p_val:
            winner = "banker"
        else:
            winner = "tie"

        return {
            "winner": winner,
            "player_val": p_val,
            "banker_val": b_val,
            "player_hand": player_hand,
            "banker_hand": banker_hand,
        }

    def _get_player_win(self, uid: int, result: dict) -> int:
        bets = self.bets.get(uid, {})
        win = 0
        winner = result["winner"]
        p_hand = result["player_hand"]
        b_hand = result["banker_hand"]

        for pos, amount in bets.items():
            if pos == "player" and winner == "player":
                win += amount * 2      # 1:1 (不含本金)
            elif pos == "banker" and winner == "banker":
                win += int(amount * 1.95)  # 1:0.95 (抽水5%)
            elif pos == "tie" and winner == "tie":
                win += amount * 8      # 1:8
            elif pos == "player_pair":
                val1 = self._card_value(p_hand[0][0])
                val2 = self._card_value(p_hand[1][0])
                if val1 == val2:
                    win += amount * 11  # 1:11
            elif pos == "banker_pair":
                val1 = self._card_value(b_hand[0][0])
                val2 = self._card_value(b_hand[1][0])
                if val1 == val2:
                    win += amount * 11  # 1:11
            else:
                # 输了的注
                pass

        # 扣除本金
        total_bet = sum(bets.values())
        return win - total_bet
