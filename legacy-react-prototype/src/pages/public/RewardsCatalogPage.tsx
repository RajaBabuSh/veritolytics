import React, { useState } from 'react';
import { Button } from '@/components/ui/Button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/Card';
import { Badge } from '@/components/ui/Badge';
import { Zap } from 'lucide-react';
import { Link } from 'react-router-dom';
import { STANDARD_REWARDS } from '@/services/dataService';

export const RewardsCatalogPage: React.FC = () => {
  const [selectedCategory, setSelectedCategory] = useState<string>('All');

  const categories = ['All', 'Cash & Crypto', 'Gift Cards'];

  const filteredRewards =
    selectedCategory === 'All'
      ? STANDARD_REWARDS
      : STANDARD_REWARDS.filter((r) => r.category === selectedCategory);

  return (
    <div className="py-16 md:py-20">
      <div className="container mx-auto px-4 max-w-6xl">
        {/* Header */}
        <div className="text-center max-w-2xl mx-auto mb-12">
          <Badge variant="violet" className="mb-2">Rewards Store</Badge>
          <h1 className="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
            Exchange Your Points for Real Rewards
          </h1>
          <p className="mt-3 text-slate-600 text-sm sm:text-base">
            Fixed 100 points = $1.00 USD exchange rate. Automated digital disbursements with zero redemption fees.
          </p>
        </div>

        {/* Category Filter Pills */}
        <div className="flex items-center justify-center gap-2 mb-10 overflow-x-auto pb-2">
          {categories.map((cat) => (
            <button
              key={cat}
              onClick={() => setSelectedCategory(cat)}
              className={`rounded-xl px-4 py-2 text-xs font-semibold transition-all ${
                selectedCategory === cat
                  ? 'bg-brand-royal-600 text-white shadow-md shadow-brand-royal-600/20'
                  : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50'
              }`}
            >
              {cat}
            </button>
          ))}
        </div>

        {/* Rewards Grid */}
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
          {filteredRewards.map((reward) => (
            <Card key={reward.id} className="hover:shadow-xl transition-all border-slate-200 flex flex-col justify-between">
              <CardHeader>
                <div className="flex items-center justify-between mb-3">
                  <Badge variant="royal">{reward.category}</Badge>
                  <span className="text-2xl">{reward.icon}</span>
                </div>
                <CardTitle className="text-xl font-bold">{reward.title}</CardTitle>
                <CardDescription className="flex items-center gap-1.5 text-xs text-slate-500 pt-1">
                  <Zap className="h-3.5 w-3.5 text-amber-500" />
                  <span>{reward.deliveryType}</span>
                </CardDescription>
              </CardHeader>
              <CardContent>
                <div className="flex items-center justify-between pt-2 border-t border-slate-100">
                  <div>
                    <span className="text-xs text-slate-400 block">Required Points</span>
                    <span className="text-xl font-black text-brand-royal-600">
                      {reward.pointsRequired.toLocaleString()} pts
                    </span>
                  </div>
                  <Link to="/member/wallet">
                    <Button variant="default" size="sm" className="font-bold">
                      Redeem
                    </Button>
                  </Link>
                </div>
              </CardContent>
            </Card>
          ))}
        </div>
      </div>
    </div>
  );
};
