import { createSlice } from '@reduxjs/toolkit';

interface AgentBlockerState { visible: boolean }
const initialState: AgentBlockerState = { visible: false };

const agentBlockerSlice = createSlice({
  name: 'agentBlocker',
  initialState,
  reducers: {
    show: state => { state.visible = true },
    hide: state => { state.visible = false },
  },
});

export const { show, hide } = agentBlockerSlice.actions;
export default agentBlockerSlice.reducer;